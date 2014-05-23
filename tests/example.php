<?php
require __DIR__ . '/../vendor/autoload.php';

class ExampleSubject
{
    public function ImHeavy($seconds = 3)
    {
        sleep($seconds);
        return "Finally my result is here !";
    }
}

$p = new Proxy\Proxy;
$p->setSubjectObject(new ExampleSubject);
$p->setCacheObject(new Proxy\CacheAdapter\Mock());
echo str_repeat("-", 10);flush();
printf("\n%s\n", $p->ImHeavy());flush();

echo "Cached :";
echo $p->ImHeavy();
