<?php

namespace Modules\Report\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Http\Request;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Company;
use Carbon\Carbon;
use Modules\Report\Traits\ReportSalesBookTrait;
use Modules\Report\Exports\SaleBookExport;


class ReportSalesBookController extends Controller
{

    use ReportSalesBookTrait;


    public function index()
    {
        return view('report::co-sales-book.index');
    }


    /**
     *
     * @param  string $type
     * @param  Request $request
     * @return mixed
     */
    public function export($type, Request $request)
    {
        $request['summary_sales_book'] = $request->summary_sales_book === 'true';
        $company = Company::first();
        $establishment = $request->establishment_id != '' ? Establishment::find($request->establishment_id) : auth()->user()->establishment;
        $request->merge(['establishment_id' => $establishment->id]);
        $filters = $request;
        $data = $this->getData($request);
        $records = $data['records'];
        $taxes = $this->getTaxesDocuments($records);
        $summary_records = $request->summary_sales_book ? $this->getSummaryRecords($data, $request) : [];
        $report_data = compact('records', 'company', 'establishment', 'filters', 'taxes', 'summary_records');

        switch ($type) {
            case 'excel':
                return (new SaleBookExport)
                    ->records($report_data)
                    ->download('Reporte_Libro_Ventas_'.date('YmdHis').'.xlsx');
                break;
            default:
                $pdf = PDF::loadView('report::co-sales-book.report_pdf', $report_data)->setPaper('a4', 'landscape');
                $filename = 'Reporte_Libro_Ventas_'.date('YmdHis');
                return $pdf->stream($filename.'.pdf');
                break;
        }
    }

}
