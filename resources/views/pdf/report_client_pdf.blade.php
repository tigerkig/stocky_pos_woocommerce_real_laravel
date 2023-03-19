<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Client  : {{$client['client_name']}}</title>
      <link rel="stylesheet" href="{{asset('/css/pdf_style.css')}}" media="all" />
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
         <img src="{{asset('/images/'.$setting['logo'])}}">
         </div>
        
         <div id="Title-heading">
               Client  : {{$client['client_name']}}
         </div>
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Customer Details</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>Full Name:</strong> {{$client['client_name']}}</div>
                           <div><strong>Phone:</strong> {{$client['phone']}}</div>
                           <div><strong>Total Sales:</strong> {{$client['total_sales']}}</div>
                           <div><strong>Total Amount:</strong> {{$symbol}} {{$client['total_amount']}}</div>
                           <div><strong>Total Paid:</strong> {{$symbol}} {{$client['total_paid']}}</div>
                           <div><strong>Total Sales Due:</strong> {{$symbol}} {{$client['due']}}</div>
                           <div><strong>Total Sell Return Due:</strong> {{$symbol}} {{$client['return_Due']}}</div>
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
                  All Sales ( Unpaid/Partial )
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
                  @foreach ($sales as $sale)
                  <tr>
                     <td>{{$sale['date']}} </td>
                     <td>{{$sale['Ref']}}</td>
                     <td>{{$symbol}} {{$sale['paid_amount']}} </td>
                     <td>{{$symbol}} {{$sale['due']}} </td>
                     <td>{{$sale['payment_status']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
      </main>
   </body>
</html>
