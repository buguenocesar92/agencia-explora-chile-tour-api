<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class MakeScaffoldCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'make:scaffold {name : El nombre de la entidad (ej. Example)}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Genera modelo, migración, controlador, interfaz, repositorio, servicio, rutas y requests para una entidad dada, basados en el modelo';

    /**
     * Ejecuta el comando.
     */
    public function handle()
    {
        // Convertir el argumento en StudlyCase para nombres de clases y en snake_case para rutas.
        $name = Str::studly($this->argument('name'));
        $nameLower = Str::camel($name);
        $parameter = Str::snake($name);           // Ej.: task
        $prefix = Str::plural($parameter);          // Ej.: tasks
        $controllerName = "{$name}Controller";

        // 1. Crear el modelo junto con la migración.
        $this->call('make:model', [
            'name'        => $name,
            '--migration' => true,
        ]);

        // 1.1 Actualizar la migración con campos genéricos.
        $tableName = Str::plural(Str::snake($name));
        $migrationFiles = glob(database_path("migrations/*_create_{$tableName}_table.php"));
        if (count($migrationFiles) > 0) {
            $migrationPath = $migrationFiles[0];
            $migrationContent = file_get_contents($migrationPath);

            // Insertar campos genéricos justo después de la clave primaria.
            $migrationContent = preg_replace(
                '/(\$table->id\(\);)/',
                "$1\n            \$table->string('name');\n            \$table->text('description')->nullable();\n            \$table->boolean('status')->default(true);",
                $migrationContent
            );
            file_put_contents($migrationPath, $migrationContent);
            $this->info("Migración actualizada con campos genéricos en {$migrationPath}.");
        } else {
            $this->error("No se encontró la migración para la tabla {$tableName}.");
        }

        // 1.2 Actualizar el modelo para agregar la propiedad fillable.
        $modelPath = app_path("Models/{$name}.php");
        if (!file_exists($modelPath)) {
            // En algunos proyectos el modelo se crea en la raíz de app/
            $modelPath = app_path("{$name}.php");
        }
        if (file_exists($modelPath)) {
            $modelContent = file_get_contents($modelPath);
            if (strpos($modelContent, 'protected $fillable') === false) {
                $modelContent = preg_replace(
                    '/(class\s+' . $name . '\s+extends\s+\S+\s*\{)/',
                    "$1\n    protected \$fillable = ['name', 'description', 'status'];",
                    $modelContent
                );
                file_put_contents($modelPath, $modelContent);
                $this->info("Modelo {$name} actualizado con fillable.");
            }
        } else {
            $this->error("No se encontró el modelo {$name}.");
        }

        // 2. Crear el controlador y sobrescribirlo con métodos CRUD que usen los FormRequest generados.
        $this->call('make:controller', [
            'name' => $controllerName,
        ]);

        $controllerPath = app_path("Http/Controllers/{$controllerName}.php");
        $controllerContent = <<<EOT
<?php

namespace App\Http\Controllers;

use App\Services\\{$name}Service;
use App\Http\Requests\\{$name}\\Store{$name}Request;
use App\Http\Requests\\{$name}\\Update{$name}Request;

class {$controllerName} extends Controller
{
    protected {$name}Service \${$nameLower}Service;

    public function __construct({$name}Service \${$nameLower}Service)
    {
        \$this->{$nameLower}Service = \${$nameLower}Service;
    }

    public function index()
    {
        \$data = \$this->{$nameLower}Service->getAll();
        return response()->json(\$data);
    }

    public function store(Store{$name}Request \$request)
    {
        \$data = \$this->{$nameLower}Service->create(\$request->validated());
        return response()->json(\$data, 201);
    }

    public function show(\$id)
    {
        \$data = \$this->{$nameLower}Service->findById(\$id);
        return response()->json(\$data);
    }

    public function update(Update{$name}Request \$request, \$id)
    {
        \$data = \$this->{$nameLower}Service->update(\$id, \$request->validated());
        return response()->json(\$data);
    }

    public function destroy(\$id)
    {
        \$this->{$nameLower}Service->delete(\$id);
        return response()->json(['message' => '{$name} eliminado']);
    }
}
EOT;
        file_put_contents($controllerPath, $controllerContent);
        $this->info("Controlador {$controllerName} generado exitosamente.");

        // 3. Generar la interfaz, repositorio y servicio.
        $interfacePath = app_path("Repositories/Contracts/{$name}RepositoryInterface.php");
        $repositoryPath = app_path("Repositories/{$name}Repository.php");
        $servicePath = app_path("Services/{$name}Service.php");

        if (!is_dir(app_path('Repositories/Contracts'))) {
            mkdir(app_path('Repositories/Contracts'), 0755, true);
        }
        if (!is_dir(app_path('Repositories'))) {
            mkdir(app_path('Repositories'), 0755, true);
        }
        if (!is_dir(app_path('Services'))) {
            mkdir(app_path('Services'), 0755, true);
        }
        if (file_exists($interfacePath) || file_exists($repositoryPath) || file_exists($servicePath)) {
            $this->error('Algunos de los archivos de repositorio/servicio ya existen. Abortando la generación.');
            return;
        }

        $interfaceContent = <<<EOT
<?php

namespace App\Repositories\Contracts;

interface {$name}RepositoryInterface
{
    public function getAll();
    public function findById(int \$id);
    public function create(array \$data);
    public function update(int \$id, array \$data);
    public function delete(int \$id);
}
EOT;

        $repositoryContent = <<<EOT
<?php

namespace App\Repositories;

use App\Repositories\Contracts\\{$name}RepositoryInterface;
use App\Models\\{$name};

class {$name}Repository implements {$name}RepositoryInterface
{
    public function getAll()
    {
        return {$name}::all();
    }

    public function findById(int \$id)
    {
        return {$name}::findOrFail(\$id);
    }

    public function create(array \$data)
    {
        return {$name}::create(\$data);
    }

    public function update(int \$id, array \$data)
    {
        \$model = {$name}::findOrFail(\$id);
        \$model->update(\$data);
        return \$model;
    }

    public function delete(int \$id): void
    {
        \$model = {$name}::findOrFail(\$id);
        \$model->delete();
    }
}
EOT;

        $serviceContent = <<<EOT
<?php

namespace App\Services;

use App\Repositories\Contracts\\{$name}RepositoryInterface;

class {$name}Service
{
    private {$name}RepositoryInterface \$repository;

    public function __construct({$name}RepositoryInterface \$repository)
    {
        \$this->repository = \$repository;
    }

    public function getAll()
    {
        return \$this->repository->getAll();
    }

    public function findById(int \$id)
    {
        return \$this->repository->findById(\$id);
    }

    public function create(array \$data)
    {
        return \$this->repository->create(\$data);
    }

    public function update(int \$id, array \$data)
    {
        return \$this->repository->update(\$id, \$data);
    }

    public function delete(int \$id): void
    {
        \$this->repository->delete(\$id);
    }
}
EOT;
        file_put_contents($interfacePath, $interfaceContent);
        file_put_contents($repositoryPath, $repositoryContent);
        file_put_contents($servicePath, $serviceContent);
        $this->info("Scaffold generado para la entidad {$name} exitosamente.");

        // 4. Actualizar AppServiceProvider para registrar la vinculación.
        $providerPath = app_path("Providers/AppServiceProvider.php");
        $providerContent = file_get_contents($providerPath);
        $binding = "\$this->app->bind(\\App\\Repositories\\Contracts\\{$name}RepositoryInterface::class, \\App\\Repositories\\{$name}Repository::class);";
        if (strpos($providerContent, $binding) === false) {
            $providerContent = preg_replace(
                '/(public function register\(\): void\s*\{\s*)/',
                "$1\n        {$binding}\n",
                $providerContent
            );
            file_put_contents($providerPath, $providerContent);
            $this->info("Se ha registrado la vinculación en AppServiceProvider.");
        } else {
            $this->info("La vinculación ya existe en AppServiceProvider.");
        }

        // 5. Generar el archivo de rutas genérico en routes/api y actualizar routes/api.php.
        $apiDir = base_path('routes/api');
        if (!is_dir($apiDir)) {
            mkdir($apiDir, 0755, true);
        }
        $routesFilePath = $apiDir . '/' . $prefix . '.php';
        if (file_exists($routesFilePath)) {
            $this->error("El archivo de rutas {$routesFilePath} ya existe. No se sobrescribirá.");
        } else {
            $routesContent = <<<EOT
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\\{$name}Controller;

Route::group(['prefix' => '{$prefix}'], function () {
    Route::get('/', [{$name}Controller::class, 'index'])->name('{$prefix}.index');
    Route::post('/', [{$name}Controller::class, 'store'])->name('{$prefix}.store');
    Route::get('/{PARAM}', [{$name}Controller::class, 'show'])->name('{$prefix}.show');
    Route::put('/{PARAM}', [{$name}Controller::class, 'update'])->name('{$prefix}.update');
    Route::delete('/{PARAM}', [{$name}Controller::class, 'destroy'])->name('{$prefix}.destroy');
});
EOT;
            $routesContent = str_replace('PARAM', $parameter, $routesContent);
            file_put_contents($routesFilePath, $routesContent);
            $this->info("Archivo de rutas generado en routes/api/{$prefix}.php");
        }
        $mainRoutesPath = base_path('routes/api.php');
        $mainRoutesContent = file_get_contents($mainRoutesPath);
        $requireLine = "require __DIR__ . '/api/{$prefix}.php';";
        if (strpos($mainRoutesContent, $requireLine) === false) {
            file_put_contents($mainRoutesPath, "\n" . $requireLine, FILE_APPEND);
            $this->info("Se ha actualizado routes/api.php para incluir {$prefix}.php.");
        }

        // 6. Crear ApiFormRequest si no existe.
        $apiFormRequestPath = app_path("Http/Requests/ApiFormRequest.php");
        if (!file_exists($apiFormRequestPath)) {
            $apiFormRequestContent = <<<EOT
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class ApiFormRequest extends FormRequest
{
    /**
     * Forzar la respuesta en JSON cuando falla la validación.
     */
    protected function failedValidation(Validator \$validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'The given data was invalid.',
                'errors'  => \$validator->errors(),
            ], 422)
        );
    }

    /**
     * Forzar la respuesta en JSON cuando falla la autorización.
     */
    protected function failedAuthorization()
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'This action is unauthorized.',
            ], 403)
        );
    }
}
EOT;
            file_put_contents($apiFormRequestPath, $apiFormRequestContent);
            $this->info("ApiFormRequest creado en app/Http/Requests/ApiFormRequest.php");
        } else {
            $this->info("ApiFormRequest ya existe.");
        }

        // 7. Crear las requests Store y Update para la entidad basadas en el modelo.
        $requestDir = app_path("Http/Requests/{$name}");
        if (!is_dir($requestDir)) {
            mkdir($requestDir, 0755, true);
        }
        $storeRequestPath = $requestDir . "/Store{$name}Request.php";
        $updateRequestPath = $requestDir . "/Update{$name}Request.php";

        if (file_exists($storeRequestPath) || file_exists($updateRequestPath)) {
            $this->error("Las requests para {$name} ya existen.");
        } else {
            $storeRequestContent = <<<EOT
<?php

namespace App\Http\Requests\\{$name};

use App\Http\Requests\ApiFormRequest;

class Store{$name}Request extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|boolean',
        ];
    }
}
EOT;
            $updateRequestContent = <<<EOT
<?php

namespace App\Http\Requests\\{$name};

use App\Http\Requests\ApiFormRequest;

class Update{$name}Request extends ApiFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status'      => 'nullable|boolean',
        ];
    }
}
EOT;
            file_put_contents($storeRequestPath, $storeRequestContent);
            file_put_contents($updateRequestPath, $updateRequestContent);
            $this->info("Requests creadas en app/Http/Requests/{$name}/");
        }
    }
}
