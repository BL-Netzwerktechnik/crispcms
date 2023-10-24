# License System

CrispCMS supports a license system so you can safely distribute your themes and only let authorized license holders host it.

```mermaid
graph LR
  A((Issuer Private Key)) --> |Derives| C((Issuer Public Key)) 
  A -->|Signs| B((License Key))
  C -->|Validates| B
```

With Crisps License System you have built in checks against:

1. Domains - Only allow specific domains to host your Theme in
2. Expiry - Expire the license after a specific date
3. OCSP - Crisp incorporates its own Online Certificate Status Protocol so you can remotely block licenses
4. Instance ID - Lock your theme to a specific Crisp Instance.

![Generate License](_media/lic2.png)