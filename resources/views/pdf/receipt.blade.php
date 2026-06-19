<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo de Pago #{{ $payment->id }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 12px; color: #1a1a2e; padding: 40px; }
        .header { display: flex; justify-content: space-between; border-bottom: 3px solid #16213e; padding-bottom: 20px; margin-bottom: 30px; }
        .company-name { font-size: 22px; font-weight: bold; color: #16213e; }
        .company-info { font-size: 10px; color: #666; margin-top: 5px; }
        .receipt-title { text-align: right; }
        .receipt-title h2 { font-size: 20px; color: #0f3460; }
        .receipt-number { font-size: 14px; color: #e94560; font-weight: bold; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 13px; font-weight: bold; color: #0f3460; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
        .info-grid { width: 100%; }
        .info-grid td { padding: 4px 10px 4px 0; vertical-align: top; }
        .info-grid .label { font-weight: bold; color: #555; width: 160px; }
        .info-grid .value { color: #1a1a2e; }
        .amount-box { background: #f8f9fa; border: 2px solid #0f3460; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
        .amount-box .label { font-size: 12px; color: #666; text-transform: uppercase; }
        .amount-box .amount { font-size: 28px; font-weight: bold; color: #0f3460; margin: 5px 0; }
        .amount-box .balance { font-size: 11px; color: #e94560; }
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
        .signatures { width: 100%; margin-top: 60px; }
        .signatures td { width: 50%; text-align: center; padding-top: 40px; }
        .sig-line { border-top: 1px solid #333; width: 200px; margin: 0 auto; padding-top: 5px; font-size: 11px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 80px; color: rgba(15,52,96,0.05); font-weight: bold; z-index: -1; }
    </style>
</head>
<body>
    @if($payment->status === 'paid')
    <div class="watermark">PAGADO</div>
    @endif

    <table style="width: 100%; margin-bottom: 30px; border-bottom: 3px solid #16213e; padding-bottom: 20px;">
        <tr>
            <td style="width: 60%;">
                <div class="company-name">{{ $tenant->company_name ?? $tenant->name }}</div>
                <div class="company-info">
                    @if($tenant->nit) NIT: {{ $tenant->nit }}<br> @endif
                    @if($tenant->address) {{ $tenant->address }}<br> @endif
                    @if($tenant->city) {{ $tenant->city }}, {{ $tenant->department }}<br> @endif
                    @if($tenant->phone) Tel: {{ $tenant->phone }} @endif
                </div>
            </td>
            <td style="text-align: right;">
                <div style="font-size: 20px; color: #0f3460; font-weight: bold;">RECIBO DE PAGO</div>
                <div class="receipt-number">No. {{ str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">
                    Fecha: {{ $payment->paid_date ? $payment->paid_date->format('d/m/Y') : now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="section">
        <div class="section-title">Datos del Cliente</div>
        <table class="info-grid">
            <tr>
                <td class="label">Nombre:</td>
                <td class="value">{{ $client->full_name }}</td>
                <td class="label">Documento:</td>
                <td class="value">{{ $client->document_type }} {{ $client->document_number }}</td>
            </tr>
            <tr>
                <td class="label">Teléfono:</td>
                <td class="value">{{ $client->phone }}</td>
                <td class="label">Email:</td>
                <td class="value">{{ $client->email ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Inmueble</div>
        <table class="info-grid">
            <tr>
                <td class="label">Proyecto:</td>
                <td class="value">{{ $project->name }}</td>
                <td class="label">Ubicación:</td>
                <td class="value">{{ $project->location }}</td>
            </tr>
            <tr>
                <td class="label">Lote:</td>
                <td class="value">{{ $lot->full_identifier }}</td>
                <td class="label">Área:</td>
                <td class="value">{{ $lot->area }} m²</td>
            </tr>
            <tr>
                <td class="label">Monto Nominal:</td>
                <td class="value">${{ number_format($plan->original_price ?? $plan->total_price, 0, ',', '.') }} COP</td>
                <td class="label">Capital Directo Prestado:</td>
                <td class="value">${{ number_format($plan->total_price, 0, ',', '.') }} COP</td>
            </tr>
            @if(($plan->original_price ?? $plan->total_price) != $plan->total_price)
            <tr>
                <td class="label" style="color: #28a745;">Descuento aplicado:</td>
                <td class="value" style="color: #28a745;">-${{ number_format($plan->original_price - $plan->total_price, 0, ',', '.') }} COP</td>
                <td></td><td></td>
            </tr>
            @endif
            <tr>
                <td class="label">Valor de Apartado:</td>
                <td class="value">${{ number_format($plan->reservation->down_payment ?? $plan->down_payment, 0, ',', '.') }} COP</td>
                <td class="label">Cuota Inicial ({{ number_format((float)($plan->initial_payment_percentage ?? 30), 0) }}%):</td>
                <td class="value">${{ number_format((float)($plan->initial_payment_amount ?? 0), 0, ',', '.') }} COP</td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="label">Valor de la Cuota No. {{ $payment->installment_number }} de {{ $plan->total_installments }}</div>
        <div class="amount">${{ number_format($payment->amount, 0, ',', '.') }} COP</div>
        <div class="balance">
            Saldo Pendiente: ${{ number_format($remaining_balance, 0, ',', '.') }} COP
        </div>
    </div>

    <div class="section">
        <div class="section-title">Detalle del Pago</div>
        <table class="info-grid">
            <tr>
                <td class="label">Método de Pago:</td>
                <td class="value">
                    @switch($payment->payment_method)
                        @case('cash') Efectivo @break
                        @case('transfer') Transferencia @break
                        @case('check') Cheque @break
                        @default {{ $payment->payment_method }}
                    @endswitch
                </td>
                <td class="label">Referencia:</td>
                <td class="value">{{ $payment->reference_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Recibido por:</td>
                <td class="value">{{ $payment->receiver?->name ?? 'N/A' }}</td>
                <td class="label">Fecha de pago:</td>
                <td class="value">{{ $payment->paid_date?->format('d/m/Y') }}</td>
            </tr>
            @if($payment->notes)
            <tr>
                <td class="label">Observaciones:</td>
                <td class="value" colspan="3">{{ $payment->notes }}</td>
            </tr>
            @endif
        </table>
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Firma del Recibidor</div>
            </td>
            <td>
                <div class="sig-line">Firma del Cliente</div>
            </td>
        </tr>
    </table>

    <div class="footer" style="text-align: center; font-size: 9px; color: #999;">
        Este documento es un comprobante de pago oficial. Generado el {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
