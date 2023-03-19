<?php return array (
  'barryvdh/laravel-dompdf' => 
  array (
    'providers' => 
    array (
      0 => 'Barryvdh\\DomPDF\\ServiceProvider',
    ),
    'aliases' => 
    array (
      'PDF' => 'Barryvdh\\DomPDF\\Facade',
    ),
  ),
  'codexshaper/laravel-woocommerce' => 
  array (
    'providers' => 
    array (
      0 => 'Codexshaper\\WooCommerce\\WooCommerceServiceProvider',
    ),
    'aliases' => 
    array (
      'Attribute' => 'Codexshaper\\WooCommerce\\Models\\Attribute',
      'Category' => 'Codexshaper\\WooCommerce\\Models\\Category',
      'Coupon' => 'Codexshaper\\WooCommerce\\Models\\Coupon',
      'Customer' => 'Codexshaper\\WooCommerce\\Models\\Customer',
      'Note' => 'Codexshaper\\WooCommerce\\Models\\Note',
      'Order' => 'Codexshaper\\WooCommerce\\Models\\Order',
      'PaymentGateway' => 'Codexshaper\\WooCommerce\\Facades\\PaymentGateway',
      'Product' => 'Codexshaper\\WooCommerce\\Models\\Product',
      'Refund' => 'Codexshaper\\WooCommerce\\Models\\Refund',
      'Report' => 'Codexshaper\\WooCommerce\\Models\\Report',
      'Review' => 'Codexshaper\\WooCommerce\\Models\\Review',
      'Setting' => 'Codexshaper\\WooCommerce\\Models\\Setting',
      'ShippingMethod' => 'Codexshaper\\WooCommerce\\Models\\ShippingMethod',
      'ShippingZone' => 'Codexshaper\\WooCommerce\\Models\\ShippingZone',
      'ShippingZoneMethod' => 'Codexshaper\\WooCommerce\\Models\\ShippingZoneMethod',
      'System' => 'Codexshaper\\WooCommerce\\Models\\System',
      'Tag' => 'Codexshaper\\WooCommerce\\Models\\Tag',
      'Tax' => 'Codexshaper\\WooCommerce\\Models\\Tax',
      'TaxClass' => 'Codexshaper\\WooCommerce\\Models\\TaxClass',
      'Term' => 'Codexshaper\\WooCommerce\\Models\\Term',
      'Variation' => 'Codexshaper\\WooCommerce\\Models\\Variation',
      'Webhook' => 'Codexshaper\\WooCommerce\\Facades\\Webhook',
      'WooCommerce' => 'Codexshaper\\WooCommerce\\Facades\\WooCommerce',
      'WooAnalytics' => 'Codexshaper\\WooCommerce\\Facades\\WooAnalytics',
      'Query' => 'Codexshaper\\WooCommerce\\Facades\\Query',
    ),
  ),
  'facade/ignition' => 
  array (
    'providers' => 
    array (
      0 => 'Facade\\Ignition\\IgnitionServiceProvider',
    ),
    'aliases' => 
    array (
      'Flare' => 'Facade\\Ignition\\Facades\\Flare',
    ),
  ),
  'fideloper/proxy' => 
  array (
    'providers' => 
    array (
      0 => 'Fideloper\\Proxy\\TrustedProxyServiceProvider',
    ),
  ),
  'intervention/image' => 
  array (
    'providers' => 
    array (
      0 => 'Intervention\\Image\\ImageServiceProvider',
    ),
    'aliases' => 
    array (
      'Image' => 'Intervention\\Image\\Facades\\Image',
    ),
  ),
  'laravel/passport' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Passport\\PassportServiceProvider',
    ),
  ),
  'laravel/tinker' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Tinker\\TinkerServiceProvider',
    ),
  ),
  'laravel/ui' => 
  array (
    'providers' => 
    array (
      0 => 'Laravel\\Ui\\UiServiceProvider',
    ),
  ),
  'maatwebsite/excel' => 
  array (
    'providers' => 
    array (
      0 => 'Maatwebsite\\Excel\\ExcelServiceProvider',
    ),
    'aliases' => 
    array (
      'Excel' => 'Maatwebsite\\Excel\\Facades\\Excel',
    ),
  ),
  'macellan/laravel-zip' => 
  array (
    'providers' => 
    array (
      0 => 'Macellan\\Zip\\ZipServiceProvider',
    ),
    'aliases' => 
    array (
      'Zip' => 'Macellan\\Zip\\ZipFacade',
    ),
  ),
  'nesbot/carbon' => 
  array (
    'providers' => 
    array (
      0 => 'Carbon\\Laravel\\ServiceProvider',
    ),
  ),
  'nexmo/laravel' => 
  array (
    'providers' => 
    array (
      0 => 'Nexmo\\Laravel\\NexmoServiceProvider',
    ),
    'aliases' => 
    array (
      'Nexmo' => 'Nexmo\\Laravel\\Facade\\Nexmo',
    ),
  ),
  'nunomaduro/collision' => 
  array (
    'providers' => 
    array (
      0 => 'NunoMaduro\\Collision\\Adapters\\Laravel\\CollisionServiceProvider',
    ),
  ),
  'nwidart/laravel-modules' => 
  array (
    'providers' => 
    array (
      0 => 'Nwidart\\Modules\\LaravelModulesServiceProvider',
    ),
    'aliases' => 
    array (
      'Module' => 'Nwidart\\Modules\\Facades\\Module',
    ),
  ),
  'oscarafdev/migrations-generator' => 
  array (
    'providers' => 
    array (
      0 => 'Way\\Generators\\GeneratorsServiceProvider',
      1 => 'OscarAFDev\\MigrationsGenerator\\MigrationsGeneratorServiceProvider',
    ),
  ),
);