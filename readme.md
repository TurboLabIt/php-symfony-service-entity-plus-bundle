# [TurboLabIt / ServiceEntityPlusBundle](https://github.com/TurboLabIt/php-symfony-service-entity-plus-bundle)

A new Symfony bundle to create "ServiceEntities".


## 📦 Install it with composer

````shell
symfony composer require turbolabit/service-entity-plus-bundle:dev-main
````


## 👷 Build with it

### Repository:

1. Create you own `src/Repository/BaseRepository.php`:

````php
<?php
namespace App\Repository;

use TurboLabIt\ServiceEntityPlusBundle\SEPRepository;


abstract class BaseRepository extends SEPRepository {}

````

2. Extend it
3. Configure the constants (`ENTITY_CLASS` is mandatory)
4. [Use the functions](https://github.com/TurboLabIt/php-symfony-service-entity-plus-bundle/blob/main/src/SEPRepository.php)

🔖 [Production-grade example](https://github.com/TurboLabIt/TurboLab.it/blob/main/src/Repository/Cms/ArticleRepository.php)


### ServiceCollection:

1. [Create you own abstract BaseServiceCollection](https://github.com/TurboLabIt/TurboLab.it/blob/main/src/ServiceCollection/BaseServiceEntityCollection.php)
2. Extend it
3. Configure the constants (`ENTITY_CLASS` is mandatory)
4. [Use the functions](https://github.com/TurboLabIt/php-symfony-service-entity-plus-bundle/blob/main/src/SEPCollection.php)

🔖 [Production-grade example](https://github.com/TurboLabIt/TurboLab.it/blob/main/src/ServiceCollection/Cms/ArticleCollection.php)


### Version00000000000000

Create `migrations/Version00000000000000.php`:

````php
<?php
namespace DoctrineMigrations;

class Version00000000000000 extends \TurboLabIt\ServiceEntityPlusBundle\migrations\Version00000000000000 {}

````


## 🧪 Test it

````shell
bash scripts/symfony-bundle-tester.sh
````
