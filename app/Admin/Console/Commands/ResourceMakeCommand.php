<?php

namespace App\Admin\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class ResourceMakeCommand extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = '
        admin:make-resource
        {name : 短横式命名的资源名称}
        {--force : 覆盖已存在文件}
        {--model= : 指定模型}
        {--test : 生成控制器测试类}
    ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '添加一个资源，包含各种相关文件';

    protected $types = [
        'model', 'filter', 'request', 'resource', 'controller', 'test',
    ];

    /**
     * 当前正在生成的类型
     *
     * @var string
     */
    protected $nowType;

    /**
     * 各类型对应的完整的类名
     *
     * @var array
     */
    protected $classes = [];

    protected $frontendTypePathMap = [
        'api' => 'api/dummy-resources.js',
        'index' => 'views/dummy-resources/Index.vue',
        'form' => 'views/dummy-resources/Form.vue',
    ];

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!$res = $this->makeBackend()) {
            return $res;
        }

        return $this->makeFrontend();
    }

    protected function makeBackend()
    {
        foreach ($this->types as $type) {
            $this->nowType = $type;
            $this->type = Str::ucfirst($type);

            if (($type == 'model') && ($model = $this->option('model'))) {
                if (!class_exists($model)) {
                    $this->error("模型 [ {$model} ] 不存在");
                    return 0;
                } elseif (class_exists($model)) {
                    $this->classes[$type] = trim($model, '\\');
                    continue;
                }
            }

            if (($type == 'test') && (!$this->option('test'))) {
                continue;
            }

            if (parent::handle() === false) {
                return 0;
            }
            $this->classes[$type] = $this->qualifyClass($this->getNameInput());
        }

        return 1;
    }

    protected function getStub()
    {
        return __DIR__."/stubs/{$this->nowType}.stub";
    }

    protected function getNameInput()
    {
        $name = Str::studly(trim($this->argument('name')));

        if ($this->nowType == 'test') {
            $name = 'Feature\\'.$name.'ControllerTest';
        } elseif ($this->nowType != 'model') {
            $name .= $this->type;
        }

        return $name;
    }

    protected function rootNamespace()
    {
        return 'App\\Admin\\'.Str::ucfirst(Str::plural($this->nowType));
    }

    protected function getPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);
        return $this->laravel['path'].
            '/Admin/'.
            Str::ucfirst(Str::plural($this->type)).
            str_replace('\\', '/', $name).'.php';
    }

    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        if ($this->nowType == 'test') {
            $stub = str_replace('NamespacedDummyModel', $this->classes['model'], $stub);
            $stub = str_replace('dummy-resource-name', Str::plural($this->argument('name')), $stub);
        } elseif ($this->nowType == 'controller') {
            foreach (['filter', 'request', 'resource', 'model'] as $type) {
                $stub = $this->replaceDummyResource($type, $stub);
            }
        }

        return $stub;
    }

    protected function replaceDummyResource(string $type, string $stub): string
    {
        $namespaced = $this->classes[$type];
        $class = class_basename($namespaced);
        $type = Str::ucfirst($type);
        $stub = str_replace("NamespacedDummy{$type}", $namespaced, $stub);
        $stub = str_replace("Dummy{$type}", $class, $stub);

        if ($type == 'Model') {
            $model = '$'.Str::camel($class);
            $models = Str::plural($model);

            $stub = str_replace('$dummyModel', $model, $stub);
            $stub = str_replace('$dummyModels', $models, $stub);
        }

        return $stub;
    }

    protected function makeFrontend()
    {
        $name = str_replace('\\', '/', trim($this->argument('name')));
        $ucNamePieces = array_map(function ($p) {
            return Str::ucfirst(Str::camel($p));
        }, explode('/', $name));

        $noSlashName = implode('', $ucNamePieces);
        $ucDummyResourceWithSlash = implode('\\', $ucNamePieces);

        $PluralDummyResource = Str::plural($noSlashName);
        $pluralKebabDummyResource = Str::plural($name);
        $ucDummyResource = $noSlashName;
        $pluralDummyResource = lcfirst($noSlashName);

        $replaces = [
            'PluralDummyResource' => $PluralDummyResource,
            'dummy-resources' => $pluralKebabDummyResource,
            'DummyResourceWithSlash' => $ucDummyResourceWithSlash,
            'DummyResource' => $ucDummyResource,
            'dummyResources' => $pluralDummyResource,
        ];

        foreach (['api', 'index', 'form', 'routes'] as $type) {
            $content = $this->files->get(__DIR__."/stubs/frontend/{$type}.stub");
            foreach ($replaces as $search => $replace) {
                $content = str_replace($search, $replace, $content);
            }

            if ($type == 'routes') {
                $this->info('路由配置：');
                $this->line($content);
            } else {
                $relativePath = str_replace('dummy-resources', $pluralKebabDummyResource, $this->frontendTypePathMap[$type]);
                $path = $this->laravel['path.resources'].'/admin/src/'.$relativePath;

                if (
                    !$this->option('force') &&
                    $this->files->exists($path)
                ) {
                    $this->error($relativePath.' 已存在');
                    return 0;
                }

                $this->makeDirectory($path);
                $this->files->put($path, $content);

                $this->info($relativePath.' 创建成功');
            }
        }

        return 1;
    }

    protected function qualifyClass($name)
    {
        // 带有目录层级的，这里全部大写头字母
        $name = implode('\\', array_map(function ($p) {
            return Str::ucfirst($p);
        }, explode('\\', $name)));

        return parent::qualifyClass($name);
    }
}
