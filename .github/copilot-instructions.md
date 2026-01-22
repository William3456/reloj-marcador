# AI Coding Instructions for Reloj Marcador

## Project Overview
This is a Laravel 11 application for employee time tracking (reloj marcador) with a multi-tenant architecture: Empresa (Company) > Sucursales (Branches) > Departamentos (Departments) > Puestos (Positions) > Empleados (Employees).

## Architecture
- **Models**: Organized in feature subfolders under `app/Models/` (e.g., `app/Models/Empleado/Empleado.php`)
- **Controllers**: Feature-based subfolders under `app/Http/Controllers/`
- **Views**: Feature-based under `resources/views/`
- **Routes**: Grouped by role middleware in `routes/web.php`
- **Database**: Spanish table names (empleados, puestos_trabajos, etc.)

## Key Components
- **Roles**: 1=Admin (sees all), 2=Manager (branch visibility), 3=Employee (clock-in only)
- **Horarios**: Schedules with time calculations, tolerance, shift types
- **Marcacion**: Time clock functionality for employees
- **Permisos**: Permissions/leave management

## Patterns & Conventions
- **Visibility Scoping**: Use `visiblePara($user)` scope on models to filter data by user role (see `Empleado.php`)
- **Role-based Access**: Middleware `check.role:X` for route protection
- **Custom Validations**: Spanish error messages in controllers (see `EmpleadoController.php`)
- **Model Attributes**: Computed properties for display formatting (e.g., `horasLaborales` in `Horario.php`)
- **Employee Code Generation**: `armaCodigo()` method combines names (see `EmpleadoController.php`)
- **Helper Functions**: `remove_accents()` for text normalization (autoloaded from `app/Helpers/GeneralHelper.php`)

## Workflows
- **Development**: `php artisan serve` + `npm run dev`
- **Build**: `npm run build` (Vite)
- **Testing**: Standard PHPUnit, but focus on feature tests for role-based logic
- **Database**: Migrations with Spanish table names, seeders for initial data

## Frontend
- **Stack**: Tailwind CSS, Alpine.js, jQuery
- **Layout**: `x-app-layout` component
- **Tables**: DataTables integration for employee lists
- **Modals**: Custom CSS in `public/css/modal.css`

## Examples
- When adding a new feature (e.g., reports), create: Model in `app/Models/Reportes/`, Controller in `app/Http/Controllers/Reportes/`, View in `resources/views/reportes/`
- For employee-related data, always apply `visiblePara(Auth::user())` scope
- Use Spanish for UI text and validation messages
- Follow existing naming: `id_empresa`, `id_sucursal`, etc. for foreign keys

## Gotchas
- Model classes use inconsistent casing (e.g., `horario` vs `Horario`)
- Table `horarios_trabajadores` for many-to-many employee-schedule relationship
- Time calculations account for overnight shifts (add 1440 minutes)
- Employee login creation requires role assignment and unique email across tables

Reference files: `app/Models/Empleado/Empleado.php`, `routes/web.php`, `app/Http/Controllers/Empleados/EmpleadoController.php`</content>
<parameter name="filePath">d:\Sistemas\reloj-marcador\.github\copilot-instructions.md