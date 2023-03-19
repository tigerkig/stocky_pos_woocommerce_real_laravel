<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Quotation _{{$quote['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('/css/pdf_style.css')}}" media="all" />
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
         <img src="{{asset('/images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> Date: </strong>{{$quote['date']}}</div>
            <div><strong> Number: </strong> {{$quote['Ref']}}</div>
            <div><strong> Status: </strong> {{$quote['statut']}}</div>
         </div>
         <div id="Title-heading">
            Quotation  : {{$quote['Ref']}}
         </div>
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Customer Info</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>Full Name :</strong> {{$quote['client_name']}}</div>
                           <div><strong>Phone :</strong> {{$quote['client_phone']}}</div>
                           <div><strong>Email :</strong>  {{$quote['client_email']}}</div>
                           <div><strong>Address :</strong>   {{$quote['client_adr']}}</div>
                           @if($quote['client_tax'])<div><strong>Tax Number :</strong>  {{$quote['client_tax']}}</div>@endif
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
            <div id="invoice">
               <table  class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Company Info</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div id="comp">{{$setting['CompanyName']}}</div>
                           <div><strong>Phone :</strong>  {{$setting['CompanyPhone']}}</div>
                           <div><strong>Email :</strong>  {{$setting['email']}}</div>
                           <div><strong>Address :</strong>  {{$setting['CompanyAdress']}}</div>
                        </td>
                     </tr>
                  </tbody>
               </table>
            </div>
         </div>
         <div id="details_inv">
            <table class="table-sm">
               <thead>
                  <tr>
                     <th>PRODUCT</th>
                     <th>UNIT PRICE</th>
                     <th>QUANTITY</th>
                     <th>DISCOUNT</th>
                     <th>TAX</th>
                     <th>TOTAL</th>
                  </tr>
               </thead>
               <tbody>
                  @foreach ($details as $detail)
                  <tr>
                     <td>
                        <span>{{$detail['code']}} ({{$detail['name']}})</span>
                           @if($detail['is_imei'] && $detail['imei_number'] !==null)
                              <p>IMEI/SN : {{$detail['imei_number']}}</p>
                           @endif
                     </td>
                     <td>{{$detail['price']}} </td>
                     <td>{{$detail['quantity']}}/{{$detail['unitSale']}}</td>
                     <td>{{$detail['DiscountNet']}} </td>
                     <td>{{$detail['taxe']}} </td>
                     <td>{{$detail['total']}} </td>
                  </tr>
                  @endforeach
               </tbody>
            </table>
         </div>
         <div id="total">
            <table>
               <tr>
                  <td>Order Tax</td>
                  <td>{{$quote['TaxNet']}} </td>
               </tr>
               <tr>
                  <td>Discount</td>
                  <td>{{$quote['discount']}} </td>
               </tr>
               <tr>
                  <td>Shipping</td>
                  <td>{{$quote['shipping']}} </td>
               </tr>
               <tr>
                  <td>Total</td>
                  <td>{{$symbol}} {{$quote['GrandTotal']}} </td>
               </tr>
            </table>
         </div>
         <div id="signature">
            @if($setting['is_invoice_footer'] && $setting['invoice_footer'] !==null)
               <p>{{$setting['invoice_footer']}}</p>
            @endif
         </div>
      </main>
   </body>
</html>
