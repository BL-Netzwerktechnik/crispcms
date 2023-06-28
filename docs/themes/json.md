## theme.json

The `theme.json` contains all important information regarding your Theme

```js
{
  "name": "full", // The Name of your Theme
  "author": "Justin René Back <j.back@jrbit.de>", // Your Name
  "description": "Example theme.json with all possible parameters prefilled", // Description of your Theme
  "type": 1, // Unused
  "faviconFile": "assets/img/favicon.ico", // The Path to your favicon file, Crisp will proxy /favicon.ico to your specified file.
  "strict_autoloading": true, // Should the Autoloader throw an exception when a class could not be included?
  "api": { // Can be empty
    "pages": {
        "root": "RootClass.php", // The API Root specifies a file in /includes/api/ which gets executed if the root of your API domain is called. e.g. https://api.example.com
        "notFound": "NotFoundClass.php" // The API Not Found Key specifies a file in /includes/api/ which gets executed if the called API Page does not exist.
    }
  },
  "onBoot": [ // Boot files are when crisp starts, can be empty
    "includes/boot/boot1.php",
    "includes/boot/boot2.php"
  ],
  "autoload": [ // The autoloader allows you to require composer packages, own custom classes or in general PHP classes, can be empty
    "includes/class", // You can include directories
    "includes/class/MyClass.php", // Or Single Files
    "includes/vendor" // NOTE: No need to include autoloader.php as CrispCMS will automatically detect vendor directories
  ],
  "hookFile": "ThemeHook.php", // The Hook File is responsible for executing pre- and postRender functions, required
  "onInstall": { // Executed on Boot of CrispCMS
    "createTranslationKeys": "translations/", // A directory of .json files for translations
    "createKVStorageItems": { // The KV Storage is a powerful tool to access both Configs in Twig and PHP! Can be empty
      "site_name": "My Awesome Site" // PHP: Config::get("site_name") | Twig: {{ config.site_name }}
    }
  },
  "onUninstall": {
    "deleteData": true // Delete KV Storage from createKVStorageItems on uninstall
  }
}
```