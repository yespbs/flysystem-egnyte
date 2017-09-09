## flysystem-egnyte
A flysystem driver for Egnyte https://www.egnyte.com/

## Usage

```php

use League\Flysystem\Filesystem;

use Yespbs\Egnyte\Client;
use Yespbs\Egnyte\Model\File as FileClient;
use Yespbs\FlysystemEgnyte\EgnyteAdapter;

$client = new Client('domain', 'oauth token');

$fileClient = new FileClient( $client );

$adapter = new EgnyteAdapter($fileClient);

$filesystem = new Filesystem($adapter);

```
