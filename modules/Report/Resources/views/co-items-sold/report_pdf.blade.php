<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Content-Type" content="application/pdf; charset=utf-8" />
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
        <title>Artículos Vendidos</title>

        @include('report::commons.styles')
    </head>
    <body>
        @include('report::commons.header')

        <div>
            <p align="left" class="title"><strong>Artículos Vendidos</strong></p>
        </div>

        @include('report::co-items-sold.partials.filters')

        @if($records->count() > 0)
            @php
                $grouped_records = $records->groupBy(function($item) {
                    $data = $item->getDataReportSoldItems();
                    return $data['type_name'] . '-' . $data['internal_id'] . '-' . $data['name'];
                });
            @endphp
            <div class="">
                <div class="">
                    <table class="">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Código</th>
                                <th>Artículo</th>
                                <th>Cantidad</th>
                                <th>Costo</th>
                                <th>Neto</th>
                                <th>Utilidad</th>
                                <th>Impuesto</th>
                                <th>Descuento</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $total = 0;
                                $total_tax = 0;
                                $total_utility = 0;
                                $total_quantity = 0;
                                $total_net_value = 0;
                                $total_cost = 0;
                                $total_discount = 0;
                            @endphp
                            @foreach($grouped_records as $group)
                                @php
                                    $first_item = $group->first()->getDataReportSoldItems();
                                    $quantity = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['quantity'] ?? 0;
                                    });
                                    $cost = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['cost'] ?? 0;
                                    });
                                    $net_value = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['net_value'] ?? 0;
                                    });
                                    $utility = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['utility'] ?? 0;
                                    });
                                    $total_tax_item = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['total_tax'] ?? 0;
                                    });
                                    $discount = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['discount'] ?? 0;
                                    });
                                    $total_item = $group->sum(function($item) {
                                        $data = $item->getDataReportSoldItems();
                                        return $data['total'] ?? 0;
                                    });

                                    $total += $total_item;
                                    $total_tax += $total_tax_item;
                                    $total_utility += $utility;
                                    $total_quantity += $quantity;
                                    $total_net_value += $net_value;
                                    $total_cost += $cost;
                                    $total_discount += $discount;
                                @endphp
                                <tr>
                                    <td class="celda">{{ $first_item['type_name'] }}</td>
                                    <td class="celda">{{ $first_item['internal_id'] }}</td>
                                    <td class="celda">{{ $first_item['name'] }}</td>
                                    <td class="celda">{{ $quantity }}</td>
                                    <td class="celda">{{ $cost }}</td>
                                    <td class="celda">{{ $net_value }}</td>
                                    <td class="celda">{{ $utility }}</td>
                                    <td class="celda">{{ $total_tax_item }}</td>
                                    <td class="celda">{{ $discount }}</td>
                                    <td class="celda">{{ $total_item }}</td>
                                </tr>
                            @endforeach
                            <tr>
                                <td colspan="3" class="celda text-right-td">TOTALES </td>
                                <td class="celda">{{ $total_quantity }}</td>
                                <td class="celda">{{ number_format($total_cost, 2) }}</td>
                                <td class="celda">{{ number_format($total_net_value, 2) }}</td>
                                <td class="celda">{{ number_format($total_utility, 2) }}</td>
                                <td class="celda">{{ number_format($total_tax, 2) }}</td>
                                <td class="celda">{{ number_format($total_discount, 2) }}</td>
                                <td class="celda">{{ number_format($total, 2) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @else
            <div class="callout callout-info">
                <p><strong>No se encontraron registros.</strong></p>
            </div>
        @endif
    </body>
</html>
