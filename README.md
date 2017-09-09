## flysystem-egnyte
A flysystem driver for Egnyte https://www.egnyte.com/

## Usage

```php

use League\Flysystem\Filesystem;

use Yespbs\Egnyte\Client;
use Yespbs\Egnyte\Model\File as EgnyteClient;
use Yespbs\FlysystemEgnyte\EgnyteAdapter;

$client = new EgnyteClient( null, 'domain', 'oauth token' );

$adapter = new EgnyteAdapter($client);

$filesystem = new Filesystem($adapter);

```
