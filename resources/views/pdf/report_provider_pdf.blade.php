<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Supplier  : {{$provider['provider_name']}}</title>
      <link rel="stylesheet" href="{{asset('/css/pdf_style.css')}}" media="all" />
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
         <img src="{{asset('/images/'.$setting['logo'])}}">
         </div>
        
         <div id="Title-heading">
               Supplier  : {{$provider['provider_name']}}
         </div>
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Supplier Details</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>Name:</strong> {{$provider['provider_name']}}</div>
                           <div><strong>Phone:</strong> {{$provider['phone']}}</div>
                           <div><strong>Total Purchases:</strong> {{$provider['total_purchase']}}</div>
                           <div><strong>Total Amount:</strong> {{$symbol}} {{$provider['total_amount']}}</div>
                           <div><strong>Total Paid:</strong> {{$symbol}} {{$provider['total_paid']}}</div>
                           <div><strong>Total Purchases Due:</strong> {{$symbol}} {{$provider['due']}}</div>
                           <div><strong>Total Purchase Return Due:</strong> {{$symbol}} {{$provider['return_Due']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <div id="invoice">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Company Info</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div id="comp">{{$setting['CompanyName']}}</div>
                           <div><strong>Address:</strong>  {{$setting['CompanyAdress']}}</div>
                           <div><strong>Phone:</strong>  {{$setting['CompanyPhone']}}</div>
                           <div><strong>Email:</strong>  {{$setting['email']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div id="details_inv">
            <h3 style="margin-bottom:10px">
                  All Purchases ( Unpaid/Partial )
            </h3>
            <table  class="table-sm">
               <thead>
                  <tr>
                     <th>DATE</th>
                     <th>REF</th>
                     <th>PAID</th>
                     <th>DUE</th>
                     <th>PAYMENT STATUS</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($purchases as $purchase)
                  <tr>
                     <td>{{$purchase['date']}} </td>
                     <td>{{$purchase['Ref']}}</td>
                     <td>{{$symbol}} {{$purchase['paid_amount']}} </td>
                     <td>{{$symbol}} {{$purchase['due']}} </td>
                     <td>{{$purchase['payment_status']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </main>
   </body>
</html>
