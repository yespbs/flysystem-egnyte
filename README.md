## flysystem-egnyte
A flysystem driver for Egnyte https://www.egnyte.com/

## Usage

```php

use League\Flysystem\Filesystem;

use Yespbs\Egnyte\Client;
use Yespbs\Egnyte\Model\File as FileClient;
use Yespbs\FlysystemEgnyte\EgnyteAdapter;

$fileClient = new FileClient( null, 'domain', 'oauth token' );

$adapter = new EgnyteAdapter($fileClient);

$filesystem = new Filesystem($adapter);

```
