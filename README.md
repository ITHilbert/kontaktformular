# Kontaktformular

Stellt ein Kontaktformular zu Verfügung.

## Benötigte Packages
Laravel-Kit
```
composer require ithilbert/laravel-kit:dev-master
```

## Installation
```
composer require ithilbert/kontaktformular
```

Config File kopieren
```
php artisan vendor:publish --provider="ITHilbert\Kontaktformular\KontaktformularServiceProvider" 
```

Configurieren
unter config/kontaktformular.php muss die Komponenten nun configuriert werden.



Zu den anderen Vue Components folgendes hinzufügen:
```
//Kontaktformular
Vue.component('kontaktformular', require('./../../vendor/ithilbert/kontaktformular/src/Resources/Vue/kontaktformular.vue').default);
```

Include Kontaktformular
Hinweis: Es muss noch im "vue-app" div sein.
```
@include('kontaktformular::show')
```





### config/app.php
Den Punkt Providers um folgenden Eintrag ergänzen:
```
\ITHilbert\LaravelKit\LaravelKitServiceProvider::class,
\ITHilbert\Kontaktformular\KontaktformularServiceProvider::class,
```



### ToDo


### Author
IT-Hilbert GmbH

Access, Excel, VBA und Web Programmierungen

Homepage: [IT-Hilbert.com](https://www.IT-Hilbert.com) 
