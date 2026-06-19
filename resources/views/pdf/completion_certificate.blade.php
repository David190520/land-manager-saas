<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Paz y Salvo - Contrato {{ $plan->id }}</title>
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
        .amount-box { background: #e6faed; border: 2px solid #28a745; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0; }
        .amount-box .label { font-size: 14px; color: #1b5e20; text-transform: uppercase; font-weight: bold; }
        .amount-box .amount { font-size: 12px; color: #28a745; margin-top: 10px; }
        .footer { margin-top: 50px; border-top: 1px solid #ddd; padding-top: 20px; }
        .signatures { width: 100%; margin-top: 80px; }
        .signatures td { width: 100%; text-align: center; padding-top: 40px; }
        .sig-line { border-top: 1px solid #333; width: 250px; margin: 0 auto; padding-top: 5px; font-size: 11px; }
        .watermark { position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg); font-size: 60px; color: rgba(40,167,69,0.05); font-weight: bold; z-index: -1; text-align: center; }
        .content { font-size: 13px; line-height: 1.6; text-align: justify; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="watermark">PAZ Y SALVO<br>COMPLETADO</div>

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
                <div style="font-size: 20px; color: #0f3460; font-weight: bold;">CERTIFICADO DE PAZ Y SALVO</div>
                <div class="receipt-number">Contrato No. {{ str_pad($plan->id, 6, '0', STR_PAD_LEFT) }}</div>
                <div style="font-size: 10px; color: #666; margin-top: 5px;">
                    Fecha de Expedición: {{ now()->format('d/m/Y') }}
                </div>
            </td>
        </tr>
    </table>

    <div class="content">
        Se certifica por medio del presente documento que el Sr./Sra. <strong>{{ $client->full_name }}</strong>, identificado(a) con <strong>{{ $client->document_type }} {{ $client->document_number }}</strong>, ha realizado el pago total y ha cumplido a cabalidad con las obligaciones financieras correspondientes al contrato de compraventa del siguiente inmueble, no presentando saldos pendientes ni deuda alguna a favor de <strong>{{ $tenant->company_name ?? $tenant->name }}</strong>.
    </div>

    <div class="section">
        <div class="section-title">Detalles del Inmueble</div>
        <table class="info-grid">
            <tr>
                <td class="label">Proyecto:</td>
                <td class="value">{{ $project->name }}</td>
                <td class="label">Ubicación:</td>
                <td class="value">{{ $project->location }}</td>
            </tr>
            <tr>
                <td class="label">Lote / Inmueble:</td>
                <td class="value">{{ $lot->full_identifier }}</td>
                <td class="label">Área:</td>
                <td class="value">{{ $lot->area }} m²</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Resumen Financiero del Contrato</div>
        <table class="info-grid">
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
            <tr>
                <td class="label">Total de Cuotas:</td>
                <td class="value">{{ $plan->total_installments }}</td>
                <td class="label">Total Pagado:</td>
                <td class="value">${{ number_format($plan->total_paid, 0, ',', '.') }} COP</td>
            </tr>
        </table>
    </div>

    <div class="amount-box">
        <div class="label">ESTADO DEL CONTRATO: PAGADO EN SU TOTALIDAD</div>
        <div class="amount">El saldo actual es de $0 COP</div>
    </div>

    <div class="content">
        Se expide este certificado a solicitud del interesado para los fines legales y administrativos que considere pertinentes. Al momento de la expedición del presente certificado de Paz y Salvo, la empresa procederá con los trámites correspondientes para la formalización y escrituración del inmueble, según dicten los términos contractuales.
    </div>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Firma Autorizada<br>{{ $tenant->company_name ?? $tenant->name }}</div>
            </td>
        </tr>
    </table>

    <div class="footer" style="text-align: center; font-size: 9px; color: #999;">
        Documento generado electrónicamente el {{ now()->format('d/m/Y H:i') }}.
    </div>
</body>
</html>
