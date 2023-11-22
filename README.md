# Kontaktformular

Stellt ein Kontaktformular zu Verfügung.


## Installation
```
composer require ithilbert/kontaktformular
```

### Config File kopieren
```
php artisan vendor:publish --provider="ITHilbert\Kontaktformular\KontaktformularServiceProvider" 
```

### config/app.php
Den Punkt Providers um folgenden Eintrag ergänzen:
```
\ITHilbert\Kontaktformular\KontaktformularServiceProvider::class,
```


### config/kontaktformular.php
Die Komponenten muss noch konfiguriert werden.

### Zu den anderen Vue Components folgendes hinzufügen:
```
//Kontaktformular
Vue.component('kontaktformular', require('./../../vendor/ithilbert/kontaktformular/src/Resources/Vue/kontaktformular.vue').default);
```

danach in der Shell
```
npm run prod
```


## Include Kontaktformular
Hinweis: Es muss noch im "vue-app" div sein.
```
@include('kontaktformular::show')
```

## .gitignore
Die Dateien sollen nicht mit bei git gespeichert werden. Deshalb fügen Sie bitte folgendes in die .gitignore ein.
```
/storage/app/kontaktformular/
```


### ToDo


### Author
IT-Hilbert GmbH

Access, Excel, VBA und Web Programmierungen

Homepage: [IT-Hilbert.com](https://www.IT-Hilbert.com) 
