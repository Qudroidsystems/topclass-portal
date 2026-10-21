{{-- resources/views/schoolpayment/studentinvoicepdf.blade.php --}}
{{-- Layout lives in partials/invoice-print so the PDF and the browser
     Print button produce the same single-page invoice. --}}
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Invoice {{ $invoiceNumber }}</title>
</head>
<body>
@include('schoolpayment.partials.invoice-print')
</body>
</html>
