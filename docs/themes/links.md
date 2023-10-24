# Links

Crisp provides a helper function both exposed in Twig and PHP, to generate crisp compatible links as well as UTM links.

<!-- tabs:start -->

#### **PHP**
```php
<?php
use crisp\api\Helper;

$LocalLink = Helper::generateLink(Path: "hello/world"); # Generates the path /hello/world
$LocalUTMLink = Helper::generateLink(Path: "hello/world", UtmID: "some_id", UtmSource: "my_source", UtmMedium: "my_medium", UtmCampaign: "my_campaign", UtmContent: "my_content" ); # Generates the path /hello/world?utm_id=some_id&utm_source=my_source&utm_medium=my_medium&utm_campaign=my_campaign&utm_content=my_content
$ExternalLink = Helper::generateLink(Path: "https://google.com", External: true): # Generates the path https://google.com
```

#### **Twig**
```twig
{# Local Link #}
{{ generateLink("hello/world") }} {# Generates the path /hello/world #}

{# Local UTM Link #}
{{ generateLink("hello/world", false, false, "some_id", "my_source", "my_medium", "my_campaign", "my_content" ) }} {# Generates the Path /hello/world?utm_id=some_id&utm_source=my_source&utm_medium=my_medium&utm_campaign=my_campaign&utm_content=my_content #}

{# External Link #}
{{ generateLink("https://google.com", true) }} {# Generates the path https://google.com #}
```
<!-- tabs:end -->