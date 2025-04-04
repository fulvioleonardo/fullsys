<template>
    <el-dialog :title="titleDialog" :visible="showDialog" @open="create" width="30%" :close-on-click-modal="false" :close-on-press-escape="false" :show-close="false" append-to-body>

        <div class="row mb-4" v-if="form.response_api_message">
            <div class="col-md-12">
                <el-alert
                    :title="form.response_api_message"
                    type="success"
                    show-icon>
                </el-alert>
            </div>
        </div>

        <div class="row mb-4" v-if="form.response_message_query_zipkey && form.type_environment_id == 2">
            <div class="col-md-12">
                <el-alert
                    :title="`Consulta Zipkey: ${form.response_message_query_zipkey}`"
                    :type="form.state_document_id == 5 ? 'success' : 'error'"
                    show-icon>
                </el-alert>
            </div>
        </div>

        <div class="row" v-if="showDownload">

            <div class="col-lg-6 col-md-6 col-sm-12 text-center font-weight-bold mt-3">
                <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload(form.download_pdf)">
                    <i class="fa fa-file-pdf"></i>
                </button>
                <p>Descargar PDF</p>
            </div>
             <div class="col-lg-6 col-md-6 col-sm-12 text-center font-weight-bold mt-3">

                <button type="button" class="btn btn-lg btn-info waves-effect waves-light" @click="clickDownload(form.download_xml)">
                    <i class="fa fa-file-excel"></i>
                </button>
                 <p>Descargar XML</p>
            </div>

        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <el-input v-model="form.customer_email">
                    <el-button slot="append" icon="el-icon-message" @click="clickSendEmail" :loading="loading">Enviar</el-button>
                </el-input>
                <small class="form-control-feedback" v-if="errors.customer_email" v-text="errors.customer_email[0]"></small>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-12">
                <el-input v-model="form.whatsapp_number" placeholder="Número WhatsApp">
                    <el-button slot="append" icon="el-icon-chat-dot-round" @click="clickSendWhatsapp" :loading="loadingWhatsapp">Enviar</el-button>
                </el-input>
            </div>
        </div>
        <span slot="footer" class="dialog-footer">
            <template v-if="showClose">
                <el-button @click="clickClose">Cerrar</el-button>
            </template>
            <template v-else>
                <el-button class="list" @click="clickFinalize">Ir al listado</el-button>
                <el-button type="primary" @click="clickNewDocument">Nuevo comprobante</el-button>
            </template>
        </span>
    </el-dialog>
</template>

<script>
    export default {
        props: ['showDialog', 'recordId', 'showClose', 'showDownload'],
        data() {
            return {
                titleDialog: null,
                loading: false,
                resource: 'co-documents',
                errors: {},
                form: {},
                company: {},
                locked_emission:{},
                loadingWhatsapp: false,
                apiConfig: {},
                whatsappError: null,
            }
        },
        async created() {
            this.initForm()
            await this.$http.get(`/companies/record`)
                .then(response => {
                    if (response.data !== '') {
                        this.company = response.data.data
                    }
                })
        },
        mounted() {
            this.loadApiConfig();
        },
        methods: {
            clickDownload(download) {
                this.$http.get(`/${this.resource}/downloadFile/${this.downloadFilename(download)}`).then((response) => {

                    let res_data = response.data
                    if(!res_data.success)
                        return this.$message.error(res_data.message)

                    var byteCharacters = atob(response.data.filebase64);
                    var byteNumbers = new Array(byteCharacters.length);
                    for (var i = 0; i < byteCharacters.length; i++) {
                        byteNumbers[i] = byteCharacters.charCodeAt(i);
                    }
                    var byteArray = new Uint8Array(byteNumbers);
                    if(download.indexOf("PDF") >= 0 || download.indexOf("pdf") >= 0)
                      var file = new Blob([byteArray], { type: 'application/pdf;base64' });
                    else
                      var file = new Blob([byteArray], { type: 'application/xml;base64' });
                    var fileURL = URL.createObjectURL(file);
                    window.open(fileURL, '_blank');
                })
//                window.open(download, '_blank');
            },
            downloadFilename(filename){
              c = ""
              for(var i = filename.length - 1; i >= 0; i--){
                  if(filename.substring(i, i + 1) != "/"){
                    c = c + filename.substring(i, i + 1)
                  }
                  else
                    return c.split('').reverse().join('');
              }
              return c.split('').reverse().join('');
            },
            clickSendWhatsapp() {

                if(!this.form.customer_phone){
                    return this.$message.error('El número es obligatorio')
                }

                window.open(`https://wa.me/51${this.form.customer_phone}?text=${this.form.message_text}`, '_blank');

            },
            async clickSendWhatsapp() {
                if (!this.form.whatsapp_number) {
                    this.$message.error('Ingrese un número de WhatsApp');
                    return;
                }

                this.loadingWhatsapp = true;
                this.whatsappError = null;
                
                try {
                    const [pdfBase64] = await Promise.all([
                        this.getPdfAsBase64(this.form.download_pdf)
                    ]);

                    const payload = {
                        number: this.form.whatsapp_number,
                        message: `Documento: ${this.form.number_full}`,
                        pdf_base64: pdfBase64,
                        filename: `documento_${this.form.number_full}.pdf`
                    };

                    const { data } = await this.$http.post('/pos/whatsapp/send', payload);

                    if (data.success) {
                        this.$message.success('Documento enviado por WhatsApp exitosamente');
                    } else {
                        throw new Error(data.message);
                    }
                } catch (error) {
                    this.whatsappError = error.response?.data?.message || error.message;
                    this.$message.error(this.whatsappError);
                } finally {
                    this.loadingWhatsapp = false;
                }
            },
            loadApiConfig() {
                const config = localStorage.getItem('pos_api_config')
                if (config) {
                    this.apiConfig = JSON.parse(config)
                }
            },
            async getPdfAsBase64(url) {
                try {
                    const response = await fetch(url);
                    if (!response.ok) throw new Error('Error al obtener el PDF');
                    
                    const blob = await response.blob();
                    return new Promise((resolve) => {
                        const reader = new FileReader();
                        reader.onloadend = () => resolve(reader.result.split(',')[1]);
                        reader.readAsDataURL(blob);
                    });
                } catch (error) {
                    console.error('Error al convertir PDF a base64:', error);
                    throw error;
                }
            },
            initForm() {
                this.errors = {};
                this.form = {
                    id: null,
                    number_full:null,
                    customer_email:null,
                    customer_phone:null,
                    correlative_api:null,
                    message_text: null,
                    response_api_message: null,
                    download_pdf: null,
                    download_xml: null,
                };
            },
            async create() {
                await this.$http.get(`/${this.resource}/record/${this.recordId}`).then(response => {
                    this.form = response.data.data;
                    this.titleDialog = 'Comprobante: '+this.form.number_full;
                });

            },
            clickPrint(format){
                window.open(`/print/document/${this.form.external_id}/${format}`, '_blank');
            },
            clickSendEmail() {
                this.loading = true
                this.$http.post(`/${this.resource}/sendEmail`, {
                    email: this.form.customer_email,
                    number: this.form.correlative_api,
                    number_full: this.form.number_full
                })
                    .then(response => {
                        if (response.data.success) {
                            this.$message.success('El correo fue enviado satisfactoriamente')
                        } else {
                            this.$message.error('Error al enviar el correo')
                        }
                    })
                    .catch(error => {
                        if (error.response.status === 422) {
                            this.errors = error.response.data.errors
                        } else {
                            this.$message.error(error.response.data.message)
                        }
                    })
                    .then(() => {
                        this.loading = false
                    })
            },
            clickFinalize() {
                location.href = (this.isContingency) ? `/contingencies` : `/${this.resource}`
            },
            clickNewDocument() {
                this.clickClose()
            },
            clickClose() {
                this.$emit('update:showDialog', false)
                this.initForm()
            },
        }
    }
</script>
