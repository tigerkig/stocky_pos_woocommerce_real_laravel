<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <title>Return _{{$return_purchase['Ref']}}</title>
      <link rel="stylesheet" href="{{asset('/css/pdf_style.css')}}" media="all" />
   </head>

   <body>
      <header class="clearfix">
         <div id="logo">
         <img src="{{asset('/images/'.$setting['logo'])}}">
         </div>
         <div id="company">
            <div><strong> Date: </strong>{{$return_purchase['date']}}</div>
            <div><strong> Number: </strong> {{$return_purchase['Ref']}}</div>
            <div><strong> Purchase Ref: </strong> {{$return_purchase['purchase_ref']}}</div>
            <div><strong> Status: </strong> {{$return_purchase['statut']}}</div>
            <div><strong> Payment Status: </strong> {{$return_purchase['payment_status']}}</div>
         </div>
         <div id="Title-heading">
            Return : {{$return_purchase['Ref']}}
         </div>
         </div>
      </header>
      <main>
         <div id="details" class="clearfix">
            <div id="client">
               <table class="table-sm">
                  <thead>
                     <tr>
                        <th class="desc">Supplier Info</th>
                     </tr>
                  </thead>
                  <tbody>
                     <tr>
                        <td>
                           <div><strong>Full Name :</strong> {{$return_purchase['supplier_name']}}</div>
                           <div><strong>Phone :</strong> {{$return_purchase['supplier_phone']}}</div>
                           <div><strong>Email :</strong>  {{$return_purchase['supplier_email']}}</div>
                           <div><strong>Address :</strong>   {{$return_purchase['supplier_adr']}}</div>
                           @if($return_purchase['supplier_tax'])<div><strong>Tax Number :</strong>  {{$return_purchase['supplier_tax']}}</div>@endif
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
                           <div><strong>Phone :</strong>  {{$setting['CompanyPhone']}}</div>
                           <div><strong>Email :</strong>  {{$setting['email']}}</div>
                           <div><strong>Address  :</strong>  {{$setting['CompanyAdress']}}</div>
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
                     <th>UNIT COST</th>
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
                     <td>{{$detail['cost']}} </td>
                     <td>{{$detail['quantity']}}/{{$detail['unit_purchase']}}</td>
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
                  <td>{{$return_purchase['TaxNet']}} </td>
               </tr>
               <tr>
                  <td>Discount</td>
                  <td>{{$return_purchase['discount']}} </td>
               </tr>
               <tr>
                  <td>Shipping</td>
                  <td>{{$return_purchase['shipping']}} </td>
               </tr>
               <tr>
                  <td>Total</td>
                  <td>{{$symbol}} {{$return_purchase['GrandTotal']}} </td>
               </tr>

               <tr>
                  <td>Paid Amount</td>
                  <td>{{$symbol}} {{$return_purchase['paid_amount']}} </td>
               </tr>

               <tr>
                  <td>Due</td>
                  <td>{{$symbol}} {{$return_purchase['due']}} </td>
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