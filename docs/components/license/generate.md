## Generate a License

Crisps License System is based on the asymmetric cryptography system and consists of:

1. The Issuer Private Key (Used on your CrispCMS Instance to generate a License)
2. The Issuer Public Key (Shipped to your Customer)
3. The License Key (Shipped to your Customer, signed with your private key)

![Structure](_media/LicenseSystem.drawio.svg)

To generate a license you can either:

1. Use CrispCMS built in License Generator
2. Built your own License Generator using a theme


## Built in License Generator

![Generate License](_media/lic2.png)

The built in license generator can be accessed using the magic url `_license` e.g. `https://example.com/_license`

The following properties are available in a license:

| Field Name              | Description                                                      |
| ----------------------- | ---------------------------------------------------------------- |
| Issuer Name             | e.g. Acme Inc.                                                   |
| Domains (Optional)      | Comma separated list of domains, wildcards supported.             |
| Expiry Date             | Untick the Checkbox to not expire the License                    |
| OCSP Responder (Optional) | OCSP Responder URL for Revocation Requests, supports {{uuid}} and {{instance}} variables |
| Licensed To             | Name of the customer                                             |
| Whitelabel (Optional)   | Custom Branding                                                  |
| Additional Data (Optional) | Your own Supplied data                                           |
| Lock to Instance ID (Optional) | Locks the License to a specific CrispCMS Instance              |


After hitting `Generate` Crisp downloads an `issuer.pub` and `license.key`, ship both to your customer.


## Programmatically

You can generate a license in your own theme as well just make sure crisp knows of the issuer key

```php
$license = new \crisp\api\License(
    version: \crisp\api\License::GEN_VERSION,
    uuid: core\Crypto::UUIDv4(),
    whitelabel: "Acme Inc. CMS",
    domains: ["example.com", "*.example.com"],
    name: "Bob Inc.",
    issuer: "Acme Inc.",
    issued_at: time(),
    expires_at: time() + 3600, // 1 Hour from Issuance Date, NULL for no expiry
    data: null,
    instance: null,
    ocsp: null,
);

if(!$license->sign()){
    // Could not sign license
}

$licenseKey = $license->exportToString();
$publicKey = Config::get("license_issuer_public_key");
```

The constructor of the License class takes several parameters:


| Parameter    | Description                                                      |
| ------------ | ---------------------------------------------------------------- |
| version      | Specifies the version of the license (GEN_VERSION constant)       |
| uuid         | Generates a random UUID using UUIDv4 method                       |
| whitelabel   | Sets the whitelabel name to "Acme Inc. CMS"                       |
| domains      | Array of domains: ["example.com", "*.example.com"]                |
| name         | Sets the name of the license holder to "Bob Inc."                 |
| issuer       | Sets the name of the license issuer to "Acme Inc."                |
| issued_at    | Sets the issuance date to the current time (time() function)      |
| expires_at   | Sets the expiration date to current time + 3600 seconds           |
| data         | Set to null                                                      |
| instance     | Set to null                                                      |
| ocsp         | Set to null                                                      |


