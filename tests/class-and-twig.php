<?php
require __DIR__ . '/bootstrap.php';
$count = 0;
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$plugin/src")) as $file) {
    if ($file->getExtension() !== 'php') continue;
    $source = file_get_contents($file->getPathname());
    if (preg_match('/namespace\s+([^;]+);/', $source, $namespace) && preg_match('/(?:final\s+)?class\s+(\w+)/', $source, $name)) {
        $class = $namespace[1] . '\\' . $name[1];
        if (!class_exists($class)) throw new RuntimeException("Could not load $class");
        $count++;
    }
}
echo "Craft inheritance/autoload: $count plugin classes loaded.\n";
$model = new fortytwostudio\cookieconsent\models\SettingsModel([
    'popupBackgroundColour'=>'abcdef', 'excludeIds'=>['4','4','',-1,12],
    'sites'=>[
        'a'=>['isEnabled'=>false,'sendGtag'=>false,'popupBackgroundColour'=>'112233','excludeIds'=>[]],
        'b'=>['popupBackgroundColour'=>'445566','excludeIds'=>['8','8',0]],
    ],
]);
$a=$model->forSite('a');$b=$model->forSite('b');$c=$model->forSite('c');
if ($a->isEnabled !== false || $a->sendGtag !== false || $a->popupBackgroundColour !== '112233' || $a->excludeIds !== []) throw new RuntimeException('Site A settings isolation failed');
if ($b->popupBackgroundColour !== '445566' || $b->excludeIds !== [8]) throw new RuntimeException('Site B settings isolation failed');
if ($c->popupBackgroundColour !== 'abcdef' || $c->excludeIds !== [4,12]) throw new RuntimeException('Settings default fallback failed');
if ($model->popupBackgroundColour !== 'abcdef' || $model->excludeIds !== [4,12]) throw new RuntimeException('Settings root mutated');
if (!$a->validate() || !$b->validate() || !$c->validate()) throw new RuntimeException('Valid settings rejected');
echo "Settings behavior: UID overrides, false/empty overrides, numeric exclusions, defaults and clone isolation passed.\n";

$view=(new ReflectionClass(craft\web\View::class))->newInstanceWithoutConstructor();
$templates=[];
foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator("$plugin/src/templates")) as $file) {
    if($file->getExtension()==='twig') {
        $relative=substr($file->getPathname(),strlen("$plugin/src/templates/"));
        $templates['forty-cookieconsent/'.$relative]=file_get_contents($file->getPathname());
    }
}
$twig = new Twig\Environment(new Twig\Loader\ArrayLoader($templates), ['strict_variables'=>true]);
$extension = new class($view,$twig) extends craft\web\twig\Extension {
    // Compile syntax with Craft's real tags/functions; omit runtime-only page instrumentation.
    public function getNodeVisitors(): array {return [];}
    public function getGlobals(): array {return [];}
};
$twig->addExtension($extension);
foreach($templates as $name=>$source) {
    $twig->compileSource(new Twig\Source($source,$name));
}
echo "Twig syntax: ".count($templates)." plugin templates compiled with Craft tags and functions.\n";
