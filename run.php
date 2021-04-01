#!/usr/bin/env php
<?php
require 'vendor/autoload.php';


use Bytes\MockGenerator\GenerateMockCommand;
use Symfony\Component\Console\Application;

$application = new Application('app:generate:mocks', '1.0.0');
$command = new GenerateMockCommand();

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run();
