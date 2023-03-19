@component('mail::message')

<h3>Dear Mister or Madam,</h3>

<h3>Please find your attached Quotation : {{ $data['Ref'] }} in PDF format.</h3>
<h3>We thank you for the trust you show us.</h3>

<h3>Regards,<h3>
<h3>{{ $data['company_name'] }}<h3>
@endcomponent
