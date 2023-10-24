# License System

CrispCMS offers a robust license system, allowing you to securely distribute your themes and restrict hosting access to authorized license holders.

```mermaid
graph LR
  A((Issuer Private Key)) --> |Derives| C((Issuer Public Key)) 
  A -->|Signs| B((License Key))
  C -->|Validates| B
```

With Crisp's License System, you benefit from built-in safeguards against the following:

- Domains: Restrict theme hosting to specific domains.
- Expiry: Automatically expire licenses on a specified date.
- OCSP: Utilize Crisp's integrated Online Certificate Status Protocol to remotely block licenses.
- Instance ID: Securely bind your theme to a specific Crisp Instance.

![Generate License](_media/lic2.png)