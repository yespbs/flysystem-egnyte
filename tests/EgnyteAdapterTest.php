<?php
namespace Yespbs\FlysystemEgnyte\Test;

use Prophecy\Argument;
use Yespbs\Egnyte\Model\File as EgnyteClient;
use League\Flysystem\Config;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yespbs\FlysystemEgnyte\EgnyteAdapter;

class EgnyteAdapterTest extends TestCase
{
    /**
     * @var
     */ 
    protected $client;

    /**
     * @var
     */ 
    protected $egnyteAdapter;

    /**
     * @todo
     */ 
    public function setUp()
    {
        $this->client = $this->prophesize(EgnyteClient::class);
        $this->egnyteAdapter = new EgnyteAdapter($this->client->reveal(), 'prefix');
    }
}