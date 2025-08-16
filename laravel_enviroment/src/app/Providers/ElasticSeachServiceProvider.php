<?php
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;

$this->app->singleton(Client::class, function () {
    $b = ClientBuilder::create();
    $hosts = env('ELASTIC_HOSTS', 'http://elasticsearch:9200');
    $b->setHosts(array_map('trim', explode(',', $hosts)));
    return $b->build();
});
