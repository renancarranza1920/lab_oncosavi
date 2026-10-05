<?php

namespace Database\Seeders;

use App\Models\PlantillaReferencia;
use DB;
use Route;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\TipoExamen;
use App\Models\Examen;
use App\Models\User;
use App\Models\Cliente;
use App\Models\Muestra;
use App\Models\Perfil;
use App\Models\DetallePerfil;
use Hash;
use Illuminate\Database\Seeder;
use Log;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Log::info('Iniciando el seeder...');

        // Crear usuario administrador (misma lógica del DatabaseSeeder original)
        $admin = User::updateOrCreate([
            'nickname' => 'oncosavi',
        ], [
            'name' => 'Administración ONCOSAVI',
            'email' => config('laboratorio.correo'),
            'password' => Hash::make('admin123'),
        ]);

        Log::info('Usuario administrador creado:', ['email' => $admin->email]);

        // Crear rol "admin"
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Log::info('Rol admin creado o encontrado.');

        // Generar permisos automáticamente
        $this->call(RolesPermisosSeeder::class);

        // Limpiar caché de permisos antes de asignar
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Asignar todos los permisos al rol "admin"
        $adminRole->syncPermissions(Permission::all());

        // Asignar el rol "admin" al usuario
        $admin->assignRole($adminRole);

        Log::info('Rol y permisos asignados al usuario administrador.');

                // Cargar el catálogo completo de ONCOSAVI en el orden requerido por las FK.
        DB::transaction(function (): void {
            $this->seedTiposExamen();
            $this->seedExamenes();
            $this->seedMuestras();
            $this->seedRelacionesExamenMuestra();
            $this->seedTiposPrueba();
            $this->seedPruebas();
            $this->seedGruposEtarios();
            $this->seedValoresReferencia();
            $this->seedPerfiles();
            $this->seedDetallePerfiles();
        });

        Log::info('Seeder finalizado correctamente.');
    }

    // =====================================================================
    // Exámenes agrupados por tipo y ordenados alfabéticamente dentro del tipo.
    // =====================================================================

    private function seedTiposExamen(): void
    {
        $rows = [
            ['id' => 1, 'nombre' => 'BACTERIOLOGÍA', 'estado' => 1],
            ['id' => 2, 'nombre' => 'COAGULACIÓN', 'estado' => 1],
            ['id' => 3, 'nombre' => 'COPROLOGÍA', 'estado' => 1],
            ['id' => 4, 'nombre' => 'ELECTROLITOS', 'estado' => 1],
            ['id' => 5, 'nombre' => 'ENDOCRINOLOGÍA', 'estado' => 1],
            ['id' => 6, 'nombre' => 'HEMATOLOGÍA', 'estado' => 1],
            ['id' => 7, 'nombre' => 'INMUNOLOGÍA', 'estado' => 1],
            ['id' => 8, 'nombre' => 'MARCADORES TUMORALES', 'estado' => 1],
            ['id' => 9, 'nombre' => 'QUÍMICA SANGUÍNEA', 'estado' => 1],
            ['id' => 10, 'nombre' => 'QUÍMICA URINARIA', 'estado' => 1],
            ['id' => 11, 'nombre' => 'UROANÁLISIS', 'estado' => 1],
            ['id' => 12, 'nombre' => 'CARDIOVASCULAR', 'estado' => 1],
            ['id' => 13, 'nombre' => 'MINERALES', 'estado' => 1],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('tipo_examens')->upsert($chunk, ['id'], ['nombre', 'estado']);
        }
    }

    private function seedExamenes(): void
    {
        $rows = [

            // Tipo 1: Bacteriología
            ['id' => 1, 'tipo_examen_id' => 1, 'nombre' => 'BACILOSCOPIA-BAAR', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 2, 'tipo_examen_id' => 1, 'nombre' => 'COLORACION GRAM (FROTIS VAGINAL)', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'cultivo_secreciones', 'estado' => 1],
            ['id' => 3, 'tipo_examen_id' => 1, 'nombre' => 'COLORACIÓN DE GRAM', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 4, 'tipo_examen_id' => 1, 'nombre' => 'COPROCULTIVO', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 5, 'tipo_examen_id' => 1, 'nombre' => 'CULTIVO DE HONGOS', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'cultivo_secreciones', 'estado' => 1],
            ['id' => 6, 'tipo_examen_id' => 1, 'nombre' => 'CULTIVO DE SECRECIONES', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 7, 'tipo_examen_id' => 1, 'nombre' => 'DIRECTO KOH', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'cultivo_secreciones', 'estado' => 1],
            ['id' => 8, 'tipo_examen_id' => 1, 'nombre' => 'ESPERMOGRAMA', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 9, 'tipo_examen_id' => 1, 'nombre' => 'UROCULTIVO', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],

            // Tipo 2: Coagulación
            ['id' => 10, 'tipo_examen_id' => 2, 'nombre' => 'ANTICOAGULANTE LUPICO (CUALITATIVO)', 'es_externo' => 0, 'precio' => 40.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 11, 'tipo_examen_id' => 2, 'nombre' => 'DIMERO-D', 'es_externo' => 1, 'precio' => 50.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 12, 'tipo_examen_id' => 2, 'nombre' => 'FIBRINÓGENO', 'es_externo' => 1, 'precio' => 15.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 13, 'tipo_examen_id' => 2, 'nombre' => 'RETRACCIÓN DE COAGULO', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 14, 'tipo_examen_id' => 2, 'nombre' => 'TIEMPO DE COAGULACIÓN', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 15, 'tipo_examen_id' => 2, 'nombre' => 'TIEMPO DE SANGRAMIENTO', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 16, 'tipo_examen_id' => 2, 'nombre' => 'TIEMPO DE TROMB. PARCIAL ACT.', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 17, 'tipo_examen_id' => 2, 'nombre' => 'TIEMPO DE TROMBINA', 'es_externo' => 1, 'precio' => 12.0, 'recipiente' => 'cuagulacion', 'estado' => 1],
            ['id' => 18, 'tipo_examen_id' => 2, 'nombre' => 'TIEMPO Y VALOR DE PROTROMBINA', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'cuagulacion', 'estado' => 1],

            // Tipo 3: Coprología
            ['id' => 19, 'tipo_examen_id' => 3, 'nombre' => 'AG. SALMONELLA TYPHI', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 20, 'tipo_examen_id' => 3, 'nombre' => 'AZUL DE METILENO', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 21, 'tipo_examen_id' => 3, 'nombre' => 'CONCENTRADO EN HECES', 'es_externo' => 1, 'precio' => 8.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 22, 'tipo_examen_id' => 3, 'nombre' => 'GENERAL DE HECES', 'es_externo' => 0, 'precio' => 2.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 23, 'tipo_examen_id' => 3, 'nombre' => 'HELICOBACTER PYLORI-AG', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 24, 'tipo_examen_id' => 3, 'nombre' => 'IGM TIFOIDEA (SALMONELLA TYPHI - SALMONELLA PARATYPHI)', 'es_externo' => 0, 'precio' => 30.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 25, 'tipo_examen_id' => 3, 'nombre' => 'PH EN HECES', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 26, 'tipo_examen_id' => 3, 'nombre' => 'ROTAVIRUS EN HECES', 'es_externo' => 1, 'precio' => 25.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 27, 'tipo_examen_id' => 3, 'nombre' => 'SANGRE OCULTA', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'coprologia', 'estado' => 1],
            ['id' => 28, 'tipo_examen_id' => 3, 'nombre' => 'SUSTANCIA REDUCTORA', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'coprologia', 'estado' => 1],

            // Tipo 4: Electrolitos
            ['id' => 29, 'tipo_examen_id' => 4, 'nombre' => 'CALCIO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 30, 'tipo_examen_id' => 4, 'nombre' => 'CLORO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 31, 'tipo_examen_id' => 4, 'nombre' => 'FÓSFORO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 32, 'tipo_examen_id' => 4, 'nombre' => 'MAGNESIO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 33, 'tipo_examen_id' => 4, 'nombre' => 'POTASIO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 34, 'tipo_examen_id' => 4, 'nombre' => 'SODIO', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 5: Endocrinología
            ['id' => 35, 'tipo_examen_id' => 5, 'nombre' => 'AC. ANTITIROGLOBULINICOS (ATT)', 'es_externo' => 1, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 36, 'tipo_examen_id' => 5, 'nombre' => 'ACTH (HORMONA ADRENOCORTICOTROPICA)', 'es_externo' => 0, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 37, 'tipo_examen_id' => 5, 'nombre' => 'ANTI CCP (PÉPTIDO CÍCLICO CITRULINADO)', 'es_externo' => 1, 'precio' => 80.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 38, 'tipo_examen_id' => 5, 'nombre' => 'B-HCG-CUANT', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 39, 'tipo_examen_id' => 5, 'nombre' => 'CORTISOL AM', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 40, 'tipo_examen_id' => 5, 'nombre' => 'CORTISOL PM', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 41, 'tipo_examen_id' => 5, 'nombre' => 'ESTRADIOL (E2)', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 42, 'tipo_examen_id' => 5, 'nombre' => 'FSH (HORMONA FOLÍCULO ESTIMULANTE)', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 43, 'tipo_examen_id' => 5, 'nombre' => 'HORMONA DE CRECIMIENTO', 'es_externo' => 1, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 44, 'tipo_examen_id' => 5, 'nombre' => 'HORMONA PARATIROIDEA PHT', 'es_externo' => 0, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 45, 'tipo_examen_id' => 5, 'nombre' => 'INSULINA', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 46, 'tipo_examen_id' => 5, 'nombre' => 'INSULINA POST-PRANDIAL', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 47, 'tipo_examen_id' => 5, 'nombre' => 'LH', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 48, 'tipo_examen_id' => 5, 'nombre' => 'LH (HORMONA LUTEINIZANTE)', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 49, 'tipo_examen_id' => 5, 'nombre' => 'PROGESTERONA', 'es_externo' => 0, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 50, 'tipo_examen_id' => 5, 'nombre' => 'PROLACTINA', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 51, 'tipo_examen_id' => 5, 'nombre' => 'T3 LIBRE', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 52, 'tipo_examen_id' => 5, 'nombre' => 'T3 TOTAL', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 53, 'tipo_examen_id' => 5, 'nombre' => 'T4 LIBRE', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 54, 'tipo_examen_id' => 5, 'nombre' => 'T4 TOTAL', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 55, 'tipo_examen_id' => 5, 'nombre' => 'TESTOSTERONA', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 56, 'tipo_examen_id' => 5, 'nombre' => 'TIROGLOBULINAS', 'es_externo' => 1, 'precio' => 70.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 57, 'tipo_examen_id' => 5, 'nombre' => 'TSH 3RA GENERACION', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 6: Hematología
            ['id' => 58, 'tipo_examen_id' => 6, 'nombre' => 'CONCENTRADO STRAUT (T.CRUZI)', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 59, 'tipo_examen_id' => 6, 'nombre' => 'CÉLULAS L.E.', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 60, 'tipo_examen_id' => 6, 'nombre' => 'EOSINÓFILOS EN SANGRE', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 61, 'tipo_examen_id' => 6, 'nombre' => 'EOSINÓFILOS NASALES', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 62, 'tipo_examen_id' => 6, 'nombre' => 'ERITROSEDIMENTACIÓN', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 63, 'tipo_examen_id' => 6, 'nombre' => 'FROTIS DE SANGRE PERIFÉRICA', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 64, 'tipo_examen_id' => 6, 'nombre' => 'HB Y HT', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 65, 'tipo_examen_id' => 6, 'nombre' => 'HEMOGRAMA', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 66, 'tipo_examen_id' => 6, 'nombre' => 'LEUCOGRAMA', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 67, 'tipo_examen_id' => 6, 'nombre' => 'PLAQUETAS', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 68, 'tipo_examen_id' => 6, 'nombre' => 'PLASMODIUM (GOTA GRUESA)', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 69, 'tipo_examen_id' => 6, 'nombre' => 'RETICULOCITOS', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'hematologia', 'estado' => 1],

            // Tipo 7: Inmunología
            ['id' => 70, 'tipo_examen_id' => 7, 'nombre' => 'AC. ANTI- TRYPANOSOMA CRUZI TOTALES (CHAGAS)', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 71, 'tipo_examen_id' => 7, 'nombre' => 'AC. ANTI-TIROIDEOPEROXIDADA', 'es_externo' => 1, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 72, 'tipo_examen_id' => 7, 'nombre' => 'AC. ANTICITRULINADOS', 'es_externo' => 1, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 73, 'tipo_examen_id' => 7, 'nombre' => 'ANTI-CARDIOLIPINASIGM', 'es_externo' => 1, 'precio' => 60.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 74, 'tipo_examen_id' => 7, 'nombre' => 'ANTI-MULLERIANA', 'es_externo' => 0, 'precio' => 150.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 75, 'tipo_examen_id' => 7, 'nombre' => 'ANTIESTREPTOLISINA O (ASO)', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 76, 'tipo_examen_id' => 7, 'nombre' => 'ANTIGENO COVID-19', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 77, 'tipo_examen_id' => 7, 'nombre' => 'ANTIMIOTICONDRIALESIGG', 'es_externo' => 1, 'precio' => 60.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 78, 'tipo_examen_id' => 7, 'nombre' => 'ANTINUCLEARES AC (ANA)', 'es_externo' => 1, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 79, 'tipo_examen_id' => 7, 'nombre' => 'ANTÍGENOS FEBRILES', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 80, 'tipo_examen_id' => 7, 'nombre' => 'CHLAMYDIA TRACHOMATIS – AG', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'cultivo_secreciones', 'estado' => 1],
            ['id' => 81, 'tipo_examen_id' => 7, 'nombre' => 'DEHIDROEPIANDROSTERONA SULFATO (DHEA-SO4)', 'es_externo' => 1, 'precio' => 110.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 82, 'tipo_examen_id' => 7, 'nombre' => 'DENGUE IGG/IGM+AG(DUO)', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 83, 'tipo_examen_id' => 7, 'nombre' => 'FACTOR REUMATOIDEO (LATEX RA)', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 84, 'tipo_examen_id' => 7, 'nombre' => 'FTA - ABS (TREPONEMA)', 'es_externo' => 1, 'precio' => 130.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 85, 'tipo_examen_id' => 7, 'nombre' => 'GONORREA – AG.', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'cultivo_secreciones', 'estado' => 1],
            ['id' => 86, 'tipo_examen_id' => 7, 'nombre' => 'HELICOBACTER PYLORI AC. IGG', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 87, 'tipo_examen_id' => 7, 'nombre' => 'HEPATITIS A AC. IGM', 'es_externo' => 0, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 88, 'tipo_examen_id' => 7, 'nombre' => 'HEPATITIS B AG. DE SUPERFICIE', 'es_externo' => 0, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 89, 'tipo_examen_id' => 7, 'nombre' => 'HEPATITIS C AC', 'es_externo' => 0, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 90, 'tipo_examen_id' => 7, 'nombre' => 'HERPES IGM (TIPO II)', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 91, 'tipo_examen_id' => 7, 'nombre' => 'IGE TOTAL', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 92, 'tipo_examen_id' => 7, 'nombre' => 'INMUNOGLOBULINASIGA (MICROSOMAL)', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 93, 'tipo_examen_id' => 7, 'nombre' => 'INMUNOGLOBULINASIGE', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 94, 'tipo_examen_id' => 7, 'nombre' => 'INMUNOGLOBULINASIGG (MICROSOMAL)', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 95, 'tipo_examen_id' => 7, 'nombre' => 'INMUNOGLOBULINASIGM (MICROSOMAL)', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 96, 'tipo_examen_id' => 7, 'nombre' => 'MONOTEST', 'es_externo' => 1, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 97, 'tipo_examen_id' => 7, 'nombre' => 'PROCALCITONINA', 'es_externo' => 1, 'precio' => 80.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 98, 'tipo_examen_id' => 7, 'nombre' => 'PROTEÍNA C REACTIVA', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 99, 'tipo_examen_id' => 7, 'nombre' => 'PRUEBA DE EMBARAZO SANGRE', 'es_externo' => 0, 'precio' => 7.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 100, 'tipo_examen_id' => 7, 'nombre' => 'TIPEO SANGUÍNEO Y FACTOR RH', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 101, 'tipo_examen_id' => 7, 'nombre' => 'TOXOPLASMA GONDII IGG', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 102, 'tipo_examen_id' => 7, 'nombre' => 'TOXOPLASMA GONDII IGM', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 103, 'tipo_examen_id' => 7, 'nombre' => 'VDRL (PRUEBA DE SIFILIS)', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 104, 'tipo_examen_id' => 7, 'nombre' => 'VIH AC. (3A GENERACIÓN)', 'es_externo' => 0, 'precio' => 30.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 105, 'tipo_examen_id' => 7, 'nombre' => 'VIH PRUEBA RAPIDA', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 8: Marcadores Tumorales
            ['id' => 106, 'tipo_examen_id' => 8, 'nombre' => 'ALFA FETO PROTEINA', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 107, 'tipo_examen_id' => 8, 'nombre' => 'CA 125', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 108, 'tipo_examen_id' => 8, 'nombre' => 'CA 15-3', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 109, 'tipo_examen_id' => 8, 'nombre' => 'CA 19-9', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 110, 'tipo_examen_id' => 8, 'nombre' => 'CEA AG. CARCIOEMBRIONARIO', 'es_externo' => 1, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 111, 'tipo_examen_id' => 8, 'nombre' => 'PSA LIBRE', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 112, 'tipo_examen_id' => 8, 'nombre' => 'PSA TOTAL', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 113, 'tipo_examen_id' => 8, 'nombre' => 'RELACIÓN PSA TOTAL/LIBRE', 'es_externo' => 0, 'precio' => 60.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 9: Química Sanguínea
            ['id' => 114, 'tipo_examen_id' => 9, 'nombre' => 'ACIDO ÚRICO', 'es_externo' => 0, 'precio' => 4.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 115, 'tipo_examen_id' => 9, 'nombre' => 'ALBUMINA', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 116, 'tipo_examen_id' => 9, 'nombre' => 'AMILASA', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 117, 'tipo_examen_id' => 9, 'nombre' => 'BILIRRUBINA DIRECTA', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 118, 'tipo_examen_id' => 9, 'nombre' => 'BILIRRUBINA INDIRECTA', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 119, 'tipo_examen_id' => 9, 'nombre' => 'BILIRRUBINA TOTAL', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 120, 'tipo_examen_id' => 9, 'nombre' => 'CITOMEGALOVIRUS IGM', 'es_externo' => 0, 'precio' => 30.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 121, 'tipo_examen_id' => 9, 'nombre' => 'COLESTEROL ALTA DENSIDAD - HDL', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 122, 'tipo_examen_id' => 9, 'nombre' => 'COLESTEROL BAJA DENSIDAD - LDL', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 123, 'tipo_examen_id' => 9, 'nombre' => 'COLESTEROL MUY BAJA DENSIDAD (VLDL CALCULADO)', 'es_externo' => 1, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 124, 'tipo_examen_id' => 9, 'nombre' => 'COLESTEROL TOTAL', 'es_externo' => 0, 'precio' => 4.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 125, 'tipo_examen_id' => 9, 'nombre' => 'CREATIN FOSFOKINASA (CPK)', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 126, 'tipo_examen_id' => 9, 'nombre' => 'CREATIN FOSFOKINASA (CPKMB)', 'es_externo' => 1, 'precio' => 20.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 127, 'tipo_examen_id' => 9, 'nombre' => 'CREATININA', 'es_externo' => 0, 'precio' => 4.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 128, 'tipo_examen_id' => 9, 'nombre' => 'DESHIDROGENASA LACTIDA (LDH)', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 129, 'tipo_examen_id' => 9, 'nombre' => 'FERRITINA', 'es_externo' => 0, 'precio' => 30.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 130, 'tipo_examen_id' => 9, 'nombre' => 'FILTRADO GLOMERULAR', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 131, 'tipo_examen_id' => 9, 'nombre' => 'FOSFATASA ACIDA', 'es_externo' => 1, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 132, 'tipo_examen_id' => 9, 'nombre' => 'FOSFATASA ALCALINA', 'es_externo' => 0, 'precio' => 8.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 133, 'tipo_examen_id' => 9, 'nombre' => 'GAMMA GLUTAMIL (GCT)', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 134, 'tipo_examen_id' => 9, 'nombre' => 'GLUCOSA', 'es_externo' => 0, 'precio' => 3.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 135, 'tipo_examen_id' => 9, 'nombre' => 'GLUCOSA POST PRANDIAL', 'es_externo' => 0, 'precio' => 3.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 136, 'tipo_examen_id' => 9, 'nombre' => 'GLUCOSA TOLERANCIA 2 HORAS', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 137, 'tipo_examen_id' => 9, 'nombre' => 'GLUCOSA TOLERANCIA 3 HORAS', 'es_externo' => 0, 'precio' => 25.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 138, 'tipo_examen_id' => 9, 'nombre' => 'GLUCOSA TOLERANCIA 5 HORAS', 'es_externo' => 0, 'precio' => 40.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 139, 'tipo_examen_id' => 9, 'nombre' => 'HEMOGLOBINA GLICOSILADA AIC', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'hematologia', 'estado' => 1],
            ['id' => 140, 'tipo_examen_id' => 9, 'nombre' => 'HIERRO CAPACIDAD DE FIJACIÓN', 'es_externo' => 1, 'precio' => 20.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 141, 'tipo_examen_id' => 9, 'nombre' => 'HIERRO SÉRICO', 'es_externo' => 1, 'precio' => 10.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 142, 'tipo_examen_id' => 9, 'nombre' => 'LIPASA', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 143, 'tipo_examen_id' => 9, 'nombre' => 'NITRÓGENO UREICO', 'es_externo' => 0, 'precio' => 4.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 144, 'tipo_examen_id' => 9, 'nombre' => 'PROTEINA TOTALES Y DIF', 'es_externo' => 0, 'precio' => 12.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 145, 'tipo_examen_id' => 9, 'nombre' => 'PROTEÍNA C REACTIVA CARDIACA', 'es_externo' => 0, 'precio' => 30.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 146, 'tipo_examen_id' => 9, 'nombre' => 'TEST O\' SULLIVAN', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 147, 'tipo_examen_id' => 9, 'nombre' => 'TRANSAMINASA OXALACÉTICA', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 148, 'tipo_examen_id' => 9, 'nombre' => 'TRANSAMINASA PIRÚVICA', 'es_externo' => 0, 'precio' => 6.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 149, 'tipo_examen_id' => 9, 'nombre' => 'TRIGLICÉRIDOS', 'es_externo' => 0, 'precio' => 4.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 10: Química Urinaria
            ['id' => 150, 'tipo_examen_id' => 10, 'nombre' => 'ACIDO ÚRICO ORINA 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 151, 'tipo_examen_id' => 10, 'nombre' => 'CALCIO ORINA DE 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 152, 'tipo_examen_id' => 10, 'nombre' => 'CLORO ORINA DE 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 153, 'tipo_examen_id' => 10, 'nombre' => 'CREATININA EN ORINA AL AZAR', 'es_externo' => 1, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 154, 'tipo_examen_id' => 10, 'nombre' => 'DEPURACIÓN DE CREATININA 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 155, 'tipo_examen_id' => 10, 'nombre' => 'FÓSFORO ORINA 24H', 'es_externo' => 1, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 156, 'tipo_examen_id' => 10, 'nombre' => 'NITRÓGENO UREICO ORINA DE 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 157, 'tipo_examen_id' => 10, 'nombre' => 'POTASIO ORINA DE 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 158, 'tipo_examen_id' => 10, 'nombre' => 'PROTEÍNAS EN ORINA DE 24H', 'es_externo' => 0, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],

            // Tipo 11: Uroanálisis
            ['id' => 159, 'tipo_examen_id' => 11, 'nombre' => 'ALBUMINA EN ORINA AL AZAR', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 160, 'tipo_examen_id' => 11, 'nombre' => 'GENERAL DE ORINA', 'es_externo' => 0, 'precio' => 2.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 161, 'tipo_examen_id' => 11, 'nombre' => 'MICROALBUMINA EN ORINA AL AZAR', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 162, 'tipo_examen_id' => 11, 'nombre' => 'PROTEINA EN ORINA AL AZAR', 'es_externo' => 0, 'precio' => 10.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 163, 'tipo_examen_id' => 11, 'nombre' => 'PROTEINAS EN ORINA DE 24 HORAS', 'es_externo' => 1, 'precio' => 15.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 164, 'tipo_examen_id' => 11, 'nombre' => 'PRUEBA DE EMBARAZO EN ORINA', 'es_externo' => 0, 'precio' => 5.0, 'recipiente' => 'uroanalisis', 'estado' => 1],
            ['id' => 165, 'tipo_examen_id' => 11, 'nombre' => 'RELACION ALBUMINA/ CREATININA EN ORINA AL AZAR', 'es_externo' => 0, 'precio' => 20.0, 'recipiente' => 'uroanalisis', 'estado' => 1],

            // Tipo 12: Cardiovascular
            ['id' => 166, 'tipo_examen_id' => 12, 'nombre' => 'PROBNP', 'es_externo' => 0, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],
            ['id' => 167, 'tipo_examen_id' => 12, 'nombre' => 'TROPONINA I (CTNI)', 'es_externo' => 0, 'precio' => 35.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

            // Tipo 13: Minerales
            ['id' => 168, 'tipo_examen_id' => 13, 'nombre' => 'VITAMINA D', 'es_externo' => 0, 'precio' => 50.0, 'recipiente' => 'quimica_sanguinea', 'estado' => 1],

        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('examens')->upsert($chunk, ['id'], ['tipo_examen_id', 'nombre', 'es_externo', 'precio', 'recipiente', 'estado']);
        }
    }

    private function seedMuestras(): void
    {
        $rows = [
            ['id' => 1, 'nombre' => 'BACILOSCOPIA', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 2, 'nombre' => 'CABELLO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 3, 'nombre' => 'CULTIVO DE ESPUTO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 4, 'nombre' => 'CULTIVO DE LIQUIDO CEFALORRAQUIDEO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 5, 'nombre' => 'FLEMA', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 6, 'nombre' => 'HECES', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 7, 'nombre' => 'HISOPADO ANAL', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 8, 'nombre' => 'HISOPADO BUCAL', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 9, 'nombre' => 'HISOPADO DE HERIDAS', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 10, 'nombre' => 'HISOPADO DE OIDO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 11, 'nombre' => 'HISOPADO FARINGEO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 12, 'nombre' => 'HISOPADO OCULAR', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 13, 'nombre' => 'ORINA', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 14, 'nombre' => 'PLASMA', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 15, 'nombre' => 'SANGRE COMPLETA', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 16, 'nombre' => 'SECRECIÓN DE ABSCESO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 17, 'nombre' => 'SECRECIONES NASALES', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 18, 'nombre' => 'SECRECIONES URETRALES', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 19, 'nombre' => 'SECRECIONES VAGINALES', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 20, 'nombre' => 'SEMEN', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 21, 'nombre' => 'SUERO', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 22, 'nombre' => 'UÑAS', 'descripcion' => null, 'instrucciones_paciente' => null],
            ['id' => 23, 'nombre' => 'HISOPADO NASAL', 'descripcion' => null, 'instrucciones_paciente' => null],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('muestras')->upsert($chunk, ['id'], ['nombre', 'descripcion', 'instrucciones_paciente']);
        }
    }

    private function seedRelacionesExamenMuestra(): void
    {
        $rows = [
            ['examen_id' => 1, 'muestra_id' => 5],
            ['examen_id' => 3, 'muestra_id' => 12],
            ['examen_id' => 3, 'muestra_id' => 16],
            ['examen_id' => 3, 'muestra_id' => 17],
            ['examen_id' => 3, 'muestra_id' => 18],
            ['examen_id' => 3, 'muestra_id' => 19],
            ['examen_id' => 4, 'muestra_id' => 6],
            ['examen_id' => 5, 'muestra_id' => 2],
            ['examen_id' => 5, 'muestra_id' => 22],
            ['examen_id' => 6, 'muestra_id' => 7],
            ['examen_id' => 6, 'muestra_id' => 9],
            ['examen_id' => 6, 'muestra_id' => 16],
            ['examen_id' => 6, 'muestra_id' => 17],
            ['examen_id' => 6, 'muestra_id' => 18],
            ['examen_id' => 6, 'muestra_id' => 19],
            ['examen_id' => 6, 'muestra_id' => 23],
            ['examen_id' => 7, 'muestra_id' => 2],
            ['examen_id' => 7, 'muestra_id' => 22],
            ['examen_id' => 9, 'muestra_id' => 13],
            ['examen_id' => 12, 'muestra_id' => 14],
            ['examen_id' => 13, 'muestra_id' => 15],
            ['examen_id' => 14, 'muestra_id' => 15],
            ['examen_id' => 15, 'muestra_id' => 15],
            ['examen_id' => 16, 'muestra_id' => 14],
            ['examen_id' => 17, 'muestra_id' => 14],
            ['examen_id' => 18, 'muestra_id' => 14],
            ['examen_id' => 20, 'muestra_id' => 6],
            ['examen_id' => 22, 'muestra_id' => 6],
            ['examen_id' => 23, 'muestra_id' => 6],
            ['examen_id' => 27, 'muestra_id' => 6],
            ['examen_id' => 28, 'muestra_id' => 6],
            ['examen_id' => 29, 'muestra_id' => 21],
            ['examen_id' => 30, 'muestra_id' => 21],
            ['examen_id' => 31, 'muestra_id' => 21],
            ['examen_id' => 32, 'muestra_id' => 21],
            ['examen_id' => 33, 'muestra_id' => 21],
            ['examen_id' => 34, 'muestra_id' => 21],
            ['examen_id' => 38, 'muestra_id' => 21],
            ['examen_id' => 43, 'muestra_id' => 21],
            ['examen_id' => 44, 'muestra_id' => 21],
            ['examen_id' => 45, 'muestra_id' => 21],
            ['examen_id' => 46, 'muestra_id' => 21],
            ['examen_id' => 47, 'muestra_id' => 21],
            ['examen_id' => 49, 'muestra_id' => 21],
            ['examen_id' => 50, 'muestra_id' => 21],
            ['examen_id' => 51, 'muestra_id' => 21],
            ['examen_id' => 52, 'muestra_id' => 21],
            ['examen_id' => 53, 'muestra_id' => 21],
            ['examen_id' => 54, 'muestra_id' => 21],
            ['examen_id' => 55, 'muestra_id' => 21],
            ['examen_id' => 57, 'muestra_id' => 21],
            ['examen_id' => 59, 'muestra_id' => 15],
            ['examen_id' => 58, 'muestra_id' => 15],
            ['examen_id' => 61, 'muestra_id' => 23],
            ['examen_id' => 62, 'muestra_id' => 15],
            ['examen_id' => 63, 'muestra_id' => 15],
            ['examen_id' => 64, 'muestra_id' => 15],
            ['examen_id' => 65, 'muestra_id' => 15],
            ['examen_id' => 66, 'muestra_id' => 15],
            ['examen_id' => 68, 'muestra_id' => 15],
            ['examen_id' => 67, 'muestra_id' => 15],
            ['examen_id' => 69, 'muestra_id' => 15],
            ['examen_id' => 35, 'muestra_id' => 21],
            ['examen_id' => 71, 'muestra_id' => 21],
            ['examen_id' => 76, 'muestra_id' => 11],
            ['examen_id' => 75, 'muestra_id' => 21],
            ['examen_id' => 79, 'muestra_id' => 21],
            ['examen_id' => 77, 'muestra_id' => 21],
            ['examen_id' => 73, 'muestra_id' => 21],
            ['examen_id' => 78, 'muestra_id' => 21],
            ['examen_id' => 82, 'muestra_id' => 21],
            ['examen_id' => 83, 'muestra_id' => 21],
            ['examen_id' => 84, 'muestra_id' => 21],
            ['examen_id' => 86, 'muestra_id' => 21],
            ['examen_id' => 87, 'muestra_id' => 21],
            ['examen_id' => 88, 'muestra_id' => 21],
            ['examen_id' => 89, 'muestra_id' => 21],
            ['examen_id' => 91, 'muestra_id' => 21],
            ['examen_id' => 92, 'muestra_id' => 21],
            ['examen_id' => 94, 'muestra_id' => 21],
            ['examen_id' => 95, 'muestra_id' => 21],
            ['examen_id' => 96, 'muestra_id' => 21],
            ['examen_id' => 99, 'muestra_id' => 21],
            ['examen_id' => 98, 'muestra_id' => 21],
            ['examen_id' => 103, 'muestra_id' => 21],
            ['examen_id' => 100, 'muestra_id' => 21],
            ['examen_id' => 102, 'muestra_id' => 21],
            ['examen_id' => 104, 'muestra_id' => 21],
            ['examen_id' => 105, 'muestra_id' => 21],
            ['examen_id' => 106, 'muestra_id' => 21],
            ['examen_id' => 107, 'muestra_id' => 21],
            ['examen_id' => 108, 'muestra_id' => 21],
            ['examen_id' => 109, 'muestra_id' => 21],
            ['examen_id' => 110, 'muestra_id' => 21],
            ['examen_id' => 111, 'muestra_id' => 21],
            ['examen_id' => 112, 'muestra_id' => 21],
            ['examen_id' => 114, 'muestra_id' => 21],
            ['examen_id' => 115, 'muestra_id' => 21],
            ['examen_id' => 116, 'muestra_id' => 21],
            ['examen_id' => 117, 'muestra_id' => 21],
            ['examen_id' => 119, 'muestra_id' => 21],
            ['examen_id' => 121, 'muestra_id' => 21],
            ['examen_id' => 122, 'muestra_id' => 21],
            ['examen_id' => 124, 'muestra_id' => 21],
            ['examen_id' => 125, 'muestra_id' => 21],
            ['examen_id' => 126, 'muestra_id' => 21],
            ['examen_id' => 127, 'muestra_id' => 21],
            ['examen_id' => 128, 'muestra_id' => 21],
            ['examen_id' => 129, 'muestra_id' => 21],
            ['examen_id' => 131, 'muestra_id' => 21],
            ['examen_id' => 132, 'muestra_id' => 21],
            ['examen_id' => 133, 'muestra_id' => 21],
            ['examen_id' => 134, 'muestra_id' => 21],
            ['examen_id' => 135, 'muestra_id' => 21],
            ['examen_id' => 136, 'muestra_id' => 21],
            ['examen_id' => 137, 'muestra_id' => 21],
            ['examen_id' => 138, 'muestra_id' => 21],
            ['examen_id' => 139, 'muestra_id' => 15],
            ['examen_id' => 140, 'muestra_id' => 21],
            ['examen_id' => 141, 'muestra_id' => 21],
            ['examen_id' => 142, 'muestra_id' => 21],
            ['examen_id' => 143, 'muestra_id' => 21],
            ['examen_id' => 144, 'muestra_id' => 21],
            ['examen_id' => 145, 'muestra_id' => 21],
            ['examen_id' => 146, 'muestra_id' => 21],
            ['examen_id' => 147, 'muestra_id' => 21],
            ['examen_id' => 148, 'muestra_id' => 21],
            ['examen_id' => 149, 'muestra_id' => 21],
            ['examen_id' => 150, 'muestra_id' => 13],
            ['examen_id' => 151, 'muestra_id' => 13],
            ['examen_id' => 152, 'muestra_id' => 13],
            ['examen_id' => 154, 'muestra_id' => 13],
            ['examen_id' => 155, 'muestra_id' => 13],
            ['examen_id' => 156, 'muestra_id' => 13],
            ['examen_id' => 157, 'muestra_id' => 13],
            ['examen_id' => 158, 'muestra_id' => 13],
            ['examen_id' => 160, 'muestra_id' => 13],
            ['examen_id' => 164, 'muestra_id' => 13],
            ['examen_id' => 60, 'muestra_id' => 15],
            ['examen_id' => 113, 'muestra_id' => 21],
            ['examen_id' => 166, 'muestra_id' => 21],
            ['examen_id' => 93, 'muestra_id' => 21],
            ['examen_id' => 168, 'muestra_id' => 21],
            ['examen_id' => 167, 'muestra_id' => 21],
            ['examen_id' => 101, 'muestra_id' => 21],
            ['examen_id' => 25, 'muestra_id' => 6],
            ['examen_id' => 74, 'muestra_id' => 21],
            ['examen_id' => 37, 'muestra_id' => 21],
            ['examen_id' => 162, 'muestra_id' => 13],
            ['examen_id' => 123, 'muestra_id' => 21],
            ['examen_id' => 10, 'muestra_id' => 14],
            ['examen_id' => 165, 'muestra_id' => 13],
            ['examen_id' => 8, 'muestra_id' => 20],
            ['examen_id' => 41, 'muestra_id' => 21],
            ['examen_id' => 130, 'muestra_id' => 21],
            ['examen_id' => 118, 'muestra_id' => 21],
            ['examen_id' => 24, 'muestra_id' => 6],
            ['examen_id' => 19, 'muestra_id' => 6],
            ['examen_id' => 159, 'muestra_id' => 13],
            ['examen_id' => 39, 'muestra_id' => 21],
            ['examen_id' => 40, 'muestra_id' => 21],
            ['examen_id' => 42, 'muestra_id' => 21],
            ['examen_id' => 48, 'muestra_id' => 21],
            ['examen_id' => 36, 'muestra_id' => 21],
            ['examen_id' => 153, 'muestra_id' => 13],
            ['examen_id' => 70, 'muestra_id' => 15],
            ['examen_id' => 161, 'muestra_id' => 13],
            ['examen_id' => 90, 'muestra_id' => 21],
            ['examen_id' => 85, 'muestra_id' => 11],
            ['examen_id' => 80, 'muestra_id' => 11],
            ['examen_id' => 11, 'muestra_id' => 14],
            ['examen_id' => 120, 'muestra_id' => 21],
            ['examen_id' => 21, 'muestra_id' => 6],
            ['examen_id' => 163, 'muestra_id' => 13],
            ['examen_id' => 2, 'muestra_id' => 19],
            ['examen_id' => 56, 'muestra_id' => 21],
            ['examen_id' => 72, 'muestra_id' => 21],
            ['examen_id' => 26, 'muestra_id' => 6],
            ['examen_id' => 97, 'muestra_id' => 21],
            ['examen_id' => 81, 'muestra_id' => 21],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('examen_muestra')->insertOrIgnore($chunk);
        }
    }

    private function seedTiposPrueba(): void
    {
        $rows = [
            ['id' => 1, 'nombre' => 'MICROSCOPICO'],
            ['id' => 2, 'nombre' => 'MACROSCOPICO'],
            ['id' => 3, 'nombre' => 'FISICO - QUIMICO'],
            ['id' => 4, 'nombre' => 'LINEA ROJA'],
            ['id' => 5, 'nombre' => 'LINEA BLANCA'],
            ['id' => 6, 'nombre' => 'LINEA PLAQUETARIA'],
            ['id' => 7, 'nombre' => 'MORFOLOGIA'],
            ['id' => 8, 'nombre' => 'MORFOLOGIA ANORMAL'],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('tipos_pruebas')->upsert($chunk, ['id'], ['nombre']);
        }
    }

    private function seedPruebas(): void
    {
        $rows = [

            // Examen 1: Baciloscopia-BAAR
            ['id' => 50, 'nombre' => 'BACILOSCOPIA (BAAR)', 'examen_id' => 1, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 3: Coloración de Gram
            ['id' => 51, 'nombre' => 'COLORACIÓN DE GRAM', 'examen_id' => 3, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 4: Coprocultivo
            ['id' => 37, 'nombre' => 'MICROORGANISMOS AISLADOS', 'examen_id' => 4, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 38, 'nombre' => 'PERIODO DE INCUBACIÓN', 'examen_id' => 4, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 39, 'nombre' => 'SENSIBLE', 'examen_id' => 4, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 40, 'nombre' => 'INTERMEDIO', 'examen_id' => 4, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 41, 'nombre' => 'RESISTENTES', 'examen_id' => 4, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 5: Cultivo de hongos
            ['id' => 52, 'nombre' => 'CULTIVO DE HONGOS', 'examen_id' => 5, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 53, 'nombre' => 'TIPO DE MUESTRA', 'examen_id' => 5, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 6: Cultivo de secreciones
            ['id' => 54, 'nombre' => 'SECRECIÓN DE', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 55, 'nombre' => 'CULTIVO DE SECRECIÓN DE', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 56, 'nombre' => 'MICROORGANISMO AISLADO', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 57, 'nombre' => 'SENSIBLE', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 58, 'nombre' => 'INTERMEDIO', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 59, 'nombre' => 'RESISTENTE', 'examen_id' => 6, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 7: Directo KOH
            ['id' => 60, 'nombre' => 'KOH', 'examen_id' => 7, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 9: Urocultivo
            ['id' => 44, 'nombre' => 'MICROORGANISMOS AISLADOS', 'examen_id' => 9, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 45, 'nombre' => 'RECUENTO', 'examen_id' => 9, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 255, 'nombre' => 'SENSIBLE', 'examen_id' => 9, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 256, 'nombre' => 'INTERMEDIO', 'examen_id' => 9, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 257, 'nombre' => 'RESISTENTE', 'examen_id' => 9, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 12: Fibrinógeno
            ['id' => 61, 'nombre' => 'TIEMPO DE PROTROMBINA', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 62, 'nombre' => 'VALOR PORCENTUAL', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 63, 'nombre' => 'INR', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 64, 'nombre' => 'ISI', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 65, 'nombre' => 'RADIO', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 66, 'nombre' => 'TIEMPO DE TROMBOPLASTINA PARCIAL ACTIVA', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 67, 'nombre' => 'TIEMPO DE CUAGULACIÓN', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 69, 'nombre' => 'TIEMPO DE TROMBINA', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 70, 'nombre' => 'FIBRINOGENO', 'examen_id' => 12, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 13: Retracción de coagulo
            ['id' => 71, 'nombre' => 'RETRACCION DE COAGULO', 'examen_id' => 13, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 14: Tiempo de coagulación
            ['id' => 72, 'nombre' => 'MINUTOS - SEGUNDOS', 'examen_id' => 14, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 15: Tiempo de sangramiento
            ['id' => 68, 'nombre' => 'TIEMPO DE SANGRAMIENTO', 'examen_id' => 15, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 74, 'nombre' => 'MINUTOS - SEGUNDOS', 'examen_id' => 15, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 16: Tiempo de tromb. parcial Act.
            ['id' => 75, 'nombre' => 'TIEMPO DE TROMBOPLASTINA PARCIAL ACTIVA', 'examen_id' => 16, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 17: Tiempo de trombina
            ['id' => 80, 'nombre' => 'TIEMPO DE TROMBINA', 'examen_id' => 17, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 18: Tiempo y valor de protrombina
            ['id' => 81, 'nombre' => 'TIEMPO DE PROTROMBINA', 'examen_id' => 18, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 82, 'nombre' => 'VALOR PORCENTUAL', 'examen_id' => 18, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 83, 'nombre' => 'INR', 'examen_id' => 18, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 84, 'nombre' => 'ISI', 'examen_id' => 18, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 85, 'nombre' => 'RADIO', 'examen_id' => 18, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 20: Azul de metileno
            ['id' => 42, 'nombre' => 'RESULTADO', 'examen_id' => 20, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 22: General de heces
            ['id' => 1, 'nombre' => 'COLOR', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 3, 'nombre' => 'CONSISTENCIA', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 4, 'nombre' => 'RESTOS ALIMENTICIOS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 5, 'nombre' => 'SANGRE OCULTA', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 6, 'nombre' => 'MUCUS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 7, 'nombre' => 'OTROS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 8, 'nombre' => 'METAZOARIOS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 9, 'nombre' => 'PROTOZOARIOS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 10, 'nombre' => 'LEVADURAS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 11, 'nombre' => 'PARTICULAS DE GRASAS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 12, 'nombre' => 'MICROBIOTA INTESTINAL', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 13, 'nombre' => 'HEMATIES', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 14, 'nombre' => 'LEUCOCITOS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 15, 'nombre' => 'OTROS', 'examen_id' => 22, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],

            // Examen 23: Helicobacter Pylori-Ag
            ['id' => 86, 'nombre' => 'HELICOBACTER PYLORI - AG', 'examen_id' => 23, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 27: Sangre oculta
            ['id' => 87, 'nombre' => 'SANGRE OCULTA EN HECES (CUANTITATIVO)', 'examen_id' => 27, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 28: Sustancia Reductora
            ['id' => 43, 'nombre' => 'RESULTADOS', 'examen_id' => 28, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 29: Calcio
            ['id' => 88, 'nombre' => 'CALCIO', 'examen_id' => 29, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 30: Cloro
            ['id' => 89, 'nombre' => 'CLORO', 'examen_id' => 30, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 31: Fósforo
            ['id' => 90, 'nombre' => 'FOSFORO', 'examen_id' => 31, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 32: Magnesio
            ['id' => 91, 'nombre' => 'MAGNESIO', 'examen_id' => 32, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 33: Potasio
            ['id' => 92, 'nombre' => 'POTASIO', 'examen_id' => 33, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 34: Sodio
            ['id' => 93, 'nombre' => 'SODIO', 'examen_id' => 34, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 38: B-hcG-Cuant
            ['id' => 94, 'nombre' => 'BETA HCG CUANTITATIVO', 'examen_id' => 38, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 43: Hormona de crecimiento
            ['id' => 97, 'nombre' => 'HORMONA DE CRECIMIENTO', 'examen_id' => 43, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 44: Hormona paratiroidea PHT
            ['id' => 98, 'nombre' => 'HORMONA PARATIROIDEA (PTH)', 'examen_id' => 44, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 45: Insulina
            ['id' => 99, 'nombre' => 'INSULINA PREPRANDIAL 0 MINUTOS', 'examen_id' => 45, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 46: Insulina post-prandial
            ['id' => 100, 'nombre' => 'INSULINA 120 MINUTOS (POSTPANDRIAL)', 'examen_id' => 46, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 47: LH
            ['id' => 101, 'nombre' => 'HORMONA LEUTINIZANTE', 'examen_id' => 47, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 49: Progesterona
            ['id' => 102, 'nombre' => 'PROGESTERONA', 'examen_id' => 49, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 50: Prolactina
            ['id' => 103, 'nombre' => 'PROLACTINA', 'examen_id' => 50, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 51: T3 libre
            ['id' => 104, 'nombre' => 'T3 LIBRE (FT3)', 'examen_id' => 51, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 52: T3 total
            ['id' => 105, 'nombre' => 'TRIYODOTIRONINA (T3)', 'examen_id' => 52, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 53: T4 libre
            ['id' => 107, 'nombre' => 'T4 LIBRE (FT4)', 'examen_id' => 53, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 54: T4 total
            ['id' => 106, 'nombre' => 'TIROXINA (T4)', 'examen_id' => 54, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 55: Testosterona
            ['id' => 108, 'nombre' => 'TESTOSTERONA (TE)', 'examen_id' => 55, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 57: TSH 3ra Generacion
            ['id' => 109, 'nombre' => 'HORMONA ESTIMULANTES DE TIROIDES (TSH 3 GENERACION)', 'examen_id' => 57, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 59: Células L.E.
            ['id' => 110, 'nombre' => 'CELULAS LE', 'examen_id' => 59, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 58: Concentrado straut (T.cruzi)
            ['id' => 111, 'nombre' => 'CONCENTRADO STRAUT', 'examen_id' => 58, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 61: Eosinófilos nasales
            ['id' => 112, 'nombre' => 'EOSINOFILOS NASALES', 'examen_id' => 61, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 62: Eritrosedimentación
            ['id' => 113, 'nombre' => 'ERITROSEDIMENTACION', 'examen_id' => 62, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 63: Frotis de sangre periférica
            ['id' => 114, 'nombre' => 'LINEA ROJA', 'examen_id' => 63, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 115, 'nombre' => 'LINEA BLANCA', 'examen_id' => 63, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 116, 'nombre' => 'LINEA PLAQUETARIA', 'examen_id' => 63, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 64: Hb y Ht
            ['id' => 117, 'nombre' => 'HEMATOCRITO', 'examen_id' => 64, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 118, 'nombre' => 'HEMOGLOBINA', 'examen_id' => 64, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 65: Hemograma
            ['id' => 139, 'nombre' => 'GLÓBULOS ROJOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 140, 'nombre' => 'HEMATOCRITO', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 141, 'nombre' => 'HEMOGLOBINA', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 142, 'nombre' => 'V.C.M.', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 143, 'nombre' => 'H.C.M.', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 144, 'nombre' => 'C.H.C.M.', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 4, 'tipo_conjunto' => null],
            ['id' => 145, 'nombre' => 'GLÓBULOS BLANCOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 146, 'nombre' => 'NEUTRÓFILOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 147, 'nombre' => 'LINFOCITOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 148, 'nombre' => 'EOSINÓFILOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 149, 'nombre' => 'MONOCITOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 150, 'nombre' => 'BASÓFILOS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 5, 'tipo_conjunto' => null],
            ['id' => 151, 'nombre' => 'PLAQUETAS', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 6, 'tipo_conjunto' => null],
            ['id' => 152, 'nombre' => 'V.P.M.', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 6, 'tipo_conjunto' => null],
            ['id' => 153, 'nombre' => 'P.D.W.', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => 6, 'tipo_conjunto' => null],
            ['id' => 154, 'nombre' => 'OBSERVACIONES', 'examen_id' => 65, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 66: Leucograma
            ['id' => 134, 'nombre' => 'GLOBULOS BLANCOS', 'examen_id' => 66, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 135, 'nombre' => 'NEUTROFILOS', 'examen_id' => 66, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 136, 'nombre' => 'LINFOCITOS', 'examen_id' => 66, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 137, 'nombre' => 'EOSINOFILOS', 'examen_id' => 66, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 138, 'nombre' => 'BASOFILO', 'examen_id' => 66, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 68: Plasmodium (gota gruesa)
            ['id' => 119, 'nombre' => 'GOTA GRUESA (PLASMODIUM SSP)', 'examen_id' => 68, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 67: Plaquetas
            ['id' => 120, 'nombre' => 'PLAQUETAS', 'examen_id' => 67, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 69: Reticulocitos
            ['id' => 121, 'nombre' => 'RETICULOCITOS', 'examen_id' => 69, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 35: Ac. Antitiroglobulinicos (ATT)
            ['id' => 122, 'nombre' => 'AC. ANTI-TIROIDEOGLOBULINA', 'examen_id' => 35, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 306, 'nombre' => 'AG. SALMONELLA TYPHI', 'examen_id' => 35, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 71: Ac. Anti-tiroideoperoxidada
            ['id' => 123, 'nombre' => 'AC. ANTI.TIROIDEOPEROXIDASA', 'examen_id' => 71, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 76: Antigeno Covid-19
            ['id' => 124, 'nombre' => 'ANTIGENO COVID-19 (SARS COV-2) HISOPADO NASOFARINGEO', 'examen_id' => 76, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 75: Antiestreptolisina O (ASO)
            ['id' => 125, 'nombre' => 'ANTIESTREPTOLISINA O (ASTO)', 'examen_id' => 75, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 79: Antígenos Febriles
            ['id' => 126, 'nombre' => 'SALMONELLA TYPHI H', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 127, 'nombre' => 'SALMONELLA TYPHI O', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 128, 'nombre' => 'SALMONELLA PARATYPHI AH', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 129, 'nombre' => 'SALMONELLA PARATYPHI BH', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 130, 'nombre' => 'BRUCELLA ABORTUS', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 131, 'nombre' => 'PROTEUS OX19', 'examen_id' => 79, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 77: AntimioticondrialesIgG
            ['id' => 132, 'nombre' => 'AC. ANTIMITOCONDRIALES IGG', 'examen_id' => 77, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 73: Anti-cardiolipinasIgM
            ['id' => 133, 'nombre' => 'ANTI- CARDIOLIPINA IGM', 'examen_id' => 73, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 78: Antinucleares Ac (ANA)
            ['id' => 155, 'nombre' => 'ANA-8 (ELISA)', 'examen_id' => 78, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 82: Dengue IgG/IgM+Ag(DUO)
            ['id' => 156, 'nombre' => 'DENGUE AC. IGG', 'examen_id' => 82, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 157, 'nombre' => 'DENGUE AC. IGM', 'examen_id' => 82, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 158, 'nombre' => 'NS1', 'examen_id' => 82, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 83: Factor reumatoideo (latex RA)
            ['id' => 159, 'nombre' => 'FACTOR REUMATOIDEO (LATEX RA)', 'examen_id' => 83, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 84: FTA - ABS (treponema)
            ['id' => 160, 'nombre' => 'FTA- ABS TREPONEMA', 'examen_id' => 84, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 86: Helicobacter Pylori Ac. IgG
            ['id' => 161, 'nombre' => 'HELICOBACTER PYLORI AC. IGG', 'examen_id' => 86, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 87: Hepatitis A Ac. IgM
            ['id' => 162, 'nombre' => 'HEPATITIS A IGM', 'examen_id' => 87, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 88: Hepatitis B Ag. de superficie
            ['id' => 163, 'nombre' => 'AG. HEPATITIS B (HBSAG)', 'examen_id' => 88, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 89: Hepatitis C Ac
            ['id' => 164, 'nombre' => 'AC. HEPATITIS (ANTI-HCV)', 'examen_id' => 89, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 91: IgE total
            ['id' => 165, 'nombre' => 'INMUNOGLOBULINA E (IGE TOTAL)', 'examen_id' => 91, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 92: InmunoglobulinasIgA (microsomal)
            ['id' => 166, 'nombre' => 'INMUNOGLOBULINA IGA (MICROSOMAL)', 'examen_id' => 92, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 94: InmunoglobulinasIgG (microsomal)
            ['id' => 167, 'nombre' => 'INMUNOGLOBULINA IGG (MICROSOMAL)', 'examen_id' => 94, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 95: InmunoglobulinasIgM (microsomal)
            ['id' => 168, 'nombre' => 'INMUNOGLOBULINA IGM (MICROSOMAL)', 'examen_id' => 95, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 96: Monotest
            ['id' => 169, 'nombre' => 'MONOTEST', 'examen_id' => 96, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 99: Prueba de embarazo sangre
            ['id' => 170, 'nombre' => 'PRUEBA DE EMBARAZO EN SANGRE (B-HCG CUALITATIVA)', 'examen_id' => 99, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 98: Proteína C reactiva
            ['id' => 171, 'nombre' => 'PROTEINA C REACTIVA', 'examen_id' => 98, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 103: VDRL (PRUEBA DE SIFILIS)
            ['id' => 172, 'nombre' => 'VDRL(PRUEBA DE SIFILIS)', 'examen_id' => 103, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 100: Tipeo sanguíneo y factor Rh
            ['id' => 48, 'nombre' => 'GRUPO SANGUINEO', 'examen_id' => 100, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 49, 'nombre' => 'FACTOR RH', 'examen_id' => 100, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 102: Toxoplasma GONDII IgM
            ['id' => 173, 'nombre' => 'TOXOPLASMA GONDII IGM', 'examen_id' => 102, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 104: VIH Ac. (3a generación)
            ['id' => 174, 'nombre' => 'VIRUS DE INMUNODEFICIENCIA HUMANA (VIH 3 GENERACION)', 'examen_id' => 104, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 105: VIH prueba rapida
            ['id' => 175, 'nombre' => 'VIRUS DE INMUNODEFICIENCIA HUMANA (VIH PRUEBA RAPIDA)', 'examen_id' => 105, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 106: ALFA FETO proteina
            ['id' => 176, 'nombre' => 'ALFAFETOPROTEINA (AFP)', 'examen_id' => 106, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 107: CA 125
            ['id' => 177, 'nombre' => 'CA-125', 'examen_id' => 107, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 108: CA 15-3
            ['id' => 178, 'nombre' => 'CA 15-3', 'examen_id' => 108, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 109: CA 19-9
            ['id' => 179, 'nombre' => 'CA 19-9', 'examen_id' => 109, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 110: CEA ag. Carcioembrionario
            ['id' => 180, 'nombre' => 'CEA AG.- CARCIOEMBRIONARIO', 'examen_id' => 110, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 111: PSA libre
            ['id' => 181, 'nombre' => 'ANTIGENO PROSTATICO LIBRE (PSA LIBRE)', 'examen_id' => 111, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 112: PSA total
            ['id' => 182, 'nombre' => 'ANTIGENO PROSTATICO TOTAL (PSA TOTAL)', 'examen_id' => 112, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 114: Acido Úrico
            ['id' => 183, 'nombre' => 'ACIDO URICO', 'examen_id' => 114, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 115: Albumina
            ['id' => 184, 'nombre' => 'ALBUMINA', 'examen_id' => 115, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 116: Amilasa
            ['id' => 185, 'nombre' => 'AMILASA', 'examen_id' => 116, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 117: Bilirrubina Directa
            ['id' => 186, 'nombre' => 'BILIRRUBINA DIRECTA', 'examen_id' => 117, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 119: Bilirrubina Total
            ['id' => 187, 'nombre' => 'BILIRRUBINA TOTAL', 'examen_id' => 119, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 121: Colesterol Alta Densidad - HDL
            ['id' => 188, 'nombre' => 'COLESTEROL ALTA DENSIDAD - HDL', 'examen_id' => 121, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 122: Colesterol Baja Densidad - LDL
            ['id' => 189, 'nombre' => 'COLESTEROL BAJA DENSIDAD - LDL', 'examen_id' => 122, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 124: Colesterol total
            ['id' => 190, 'nombre' => 'COLESTEROL TOTAL', 'examen_id' => 124, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 125: Creatin Fosfokinasa (CPK)
            ['id' => 191, 'nombre' => 'CREATIN FOSFOKINASA TOTAL (CPK TOTAL)', 'examen_id' => 125, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 126: Creatin Fosfokinasa (CPKMB)
            ['id' => 192, 'nombre' => 'CREATIN FOSFOKINASA FRACCION MB (CPK - MB)', 'examen_id' => 126, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 127: Creatinina
            ['id' => 193, 'nombre' => 'CREATININA SERICA', 'examen_id' => 127, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 128: Deshidrogenasa Lactida (LDH)
            ['id' => 194, 'nombre' => 'DESHIDROGENASA LACTIDA (LDH)', 'examen_id' => 128, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 129: Ferritina
            ['id' => 195, 'nombre' => 'FERRITINA', 'examen_id' => 129, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 131: Fosfatasa Acida
            ['id' => 196, 'nombre' => 'FOSFATASA ACIDA', 'examen_id' => 131, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 132: Fosfatasa Alcalina
            ['id' => 197, 'nombre' => 'FOSFATASA ALCALINA', 'examen_id' => 132, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 133: Gamma Glutamil (GCT)
            ['id' => 198, 'nombre' => 'GAMMA GLUTAMIL TRANSPEPTIDASA (GGT)', 'examen_id' => 133, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 134: Glucosa
            ['id' => 199, 'nombre' => 'GLUCOSA EN AYUNA', 'examen_id' => 134, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 135: Glucosa Post Prandial
            ['id' => 200, 'nombre' => 'GLUCOSA POST PRANDIAL', 'examen_id' => 135, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 136: Glucosa Tolerancia 2 Horas
            ['id' => 201, 'nombre' => 'GLUCOSA EN AYUNA', 'examen_id' => 136, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 202, 'nombre' => 'GLUCOSA 1 HORA', 'examen_id' => 136, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 203, 'nombre' => 'GLUCOSA 2 HORAS', 'examen_id' => 136, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 137: Glucosa Tolerancia 3 Horas
            ['id' => 204, 'nombre' => 'GLUCOSA EN AYUNA', 'examen_id' => 137, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 205, 'nombre' => 'GLUCOSA 1 HORAS', 'examen_id' => 137, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 206, 'nombre' => 'GLUCOSA 2 HORAS', 'examen_id' => 137, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 207, 'nombre' => 'GLUCOSA 3 HORAS', 'examen_id' => 137, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 138: Glucosa Tolerancia 5 Horas
            ['id' => 208, 'nombre' => 'GLUCOSA EN AYUNA', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 209, 'nombre' => 'GLUCOSA 1 HORAS', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 210, 'nombre' => 'GLUCOSA 2 HORAS', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 211, 'nombre' => 'GLUCOSA 3 HORAS', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 212, 'nombre' => 'GLUCOSA 4 HORAS', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 213, 'nombre' => 'GLUCOSA 5 HORAS', 'examen_id' => 138, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 139: Hemoglobina Glicosilada AIC
            ['id' => 214, 'nombre' => 'HEMOGLOBINA GLICOSILADA (HBA1C)', 'examen_id' => 139, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 140: Hierro Capacidad de Fijación
            ['id' => 215, 'nombre' => 'HIERRO CAPACIDAD DE FIJACION', 'examen_id' => 140, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 141: Hierro Sérico
            ['id' => 216, 'nombre' => 'HIERRO SERICO', 'examen_id' => 141, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 142: Lipasa
            ['id' => 217, 'nombre' => 'LIPASA', 'examen_id' => 142, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 143: Nitrógeno Ureico
            ['id' => 218, 'nombre' => 'UREA', 'examen_id' => 143, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 219, 'nombre' => 'NITROGENO UREICO (BUN)', 'examen_id' => 143, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 144: Proteina totales y Dif
            ['id' => 220, 'nombre' => 'PROTEINA TOTALES', 'examen_id' => 144, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 221, 'nombre' => 'ALBUMINA', 'examen_id' => 144, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 222, 'nombre' => 'GLOBULINA', 'examen_id' => 144, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 223, 'nombre' => 'RELACION A/G', 'examen_id' => 144, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 145: Proteína C Reactiva Cardiaca
            ['id' => 224, 'nombre' => 'PCR ULTRASENSIBLE (PCR-HS)', 'examen_id' => 145, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 146: Test O' Sullivan
            ['id' => 225, 'nombre' => 'GLUCOSA EN AYUNA', 'examen_id' => 146, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 226, 'nombre' => 'GLUCOSA 1 HORA', 'examen_id' => 146, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 147: Transaminasa Oxalacética
            ['id' => 227, 'nombre' => 'TRANSAMINASA GLUTAMICO OXALACETICA  (TGO/AST)', 'examen_id' => 147, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 148: Transaminasa Pirúvica
            ['id' => 228, 'nombre' => 'TRASAMINASA GLUTAMICO PIRUVICA (TGP/ALT)', 'examen_id' => 148, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 149: Triglicéridos
            ['id' => 229, 'nombre' => 'TRIGLICERIDOS', 'examen_id' => 149, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 150: Acido úrico orina 24h
            ['id' => 230, 'nombre' => 'ACIDO URICO EN ORINA 24 HORAS}', 'examen_id' => 150, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 151: Calcio orina de 24h
            ['id' => 231, 'nombre' => 'CALCIO EN ORINA DE 24 HORAS', 'examen_id' => 151, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 152: Cloro orina de 24h
            ['id' => 232, 'nombre' => 'CLORO EN ORINA DE 24 HORAS', 'examen_id' => 152, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 154: Depuración de creatinina 24h
            ['id' => 233, 'nombre' => 'DEPURACION', 'examen_id' => 154, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 234, 'nombre' => 'CREATININA EN ORINA', 'examen_id' => 154, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 235, 'nombre' => 'CREATININA EN SANGRE', 'examen_id' => 154, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 236, 'nombre' => 'VOLUMEN', 'examen_id' => 154, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 155: Fósforo orina 24h
            ['id' => 237, 'nombre' => 'FOSFORO EN ORINA DE 24 HORAS', 'examen_id' => 155, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 156: Nitrógeno ureico orina de 24h
            ['id' => 238, 'nombre' => 'NITROGENO UREICO EN ORINA DE 24 HORAS', 'examen_id' => 156, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 157: Potasio orina de 24h
            ['id' => 239, 'nombre' => 'POTASIO DE ORINA DE 24 HORAS', 'examen_id' => 157, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 158: Proteínas en orina de 24h
            ['id' => 240, 'nombre' => 'PROTEINA EN ORINA DE 24 HORAS', 'examen_id' => 158, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 241, 'nombre' => 'VOLUMEN', 'examen_id' => 158, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 160: General de orina
            ['id' => 16, 'nombre' => 'COLOR', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 17, 'nombre' => 'ASPECTO', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 18, 'nombre' => 'PH', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 19, 'nombre' => 'DENSIDAD', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 20, 'nombre' => 'GLUCOSA', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 21, 'nombre' => 'PROTEINA', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 22, 'nombre' => 'CUERPO CETONICO', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 23, 'nombre' => 'UROBILINOGENO', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 24, 'nombre' => 'ESTERAZA LEUCOCITARIA', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 25, 'nombre' => 'SANGRE OCULTA', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 26, 'nombre' => 'NITRITOS', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 27, 'nombre' => 'BILIRRUBINA', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 28, 'nombre' => 'ACIDO ASCORBICO', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 3, 'tipo_conjunto' => null],
            ['id' => 29, 'nombre' => 'CRISTALES', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 30, 'nombre' => 'CILINDROS', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 31, 'nombre' => 'LEUCOCITOS', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 32, 'nombre' => 'HEMATÍES', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 33, 'nombre' => 'CÉLULAS EPITELIALES', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 34, 'nombre' => 'FILAMENTOS MUCOIDES', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 35, 'nombre' => 'BACTERIAS', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 36, 'nombre' => 'OTROS', 'examen_id' => 160, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],

            // Examen 164: Prueba de embarazo en orina
            ['id' => 46, 'nombre' => 'PRUEBA DE EMBARAZO', 'examen_id' => 164, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 60: Eosinófilos en Sangre
            ['id' => 242, 'nombre' => 'EOSINOFILOS EN SANGRE', 'examen_id' => 60, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 113: RELACIÓN PSA TOTAL/LIBRE
            ['id' => 243, 'nombre' => 'ANTIGENO PROSTATICO TOTAL (PSA TOTAL)', 'examen_id' => 113, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 244, 'nombre' => 'ANTIGENO PROSTATICO LIBRE (PSA LIBRE)', 'examen_id' => 113, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 245, 'nombre' => 'RELACION TOTAL LIBRE', 'examen_id' => 113, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 166: ProBNP
            ['id' => 246, 'nombre' => 'PEPTIDO NETRIURETICO TIPO B (NT-PROBNP)', 'examen_id' => 166, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 168: Vitamina D
            ['id' => 248, 'nombre' => 'VITAMINA D TOTAL', 'examen_id' => 168, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 167: Troponina I (cTnI)
            ['id' => 249, 'nombre' => 'TROPONINA I (CTNI)', 'examen_id' => 167, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 101: Toxoplasma GONDII IgG
            ['id' => 250, 'nombre' => 'TOXOPLASMA GONDII IGG', 'examen_id' => 101, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 25: pH en heces
            ['id' => 251, 'nombre' => 'PH EN HECES', 'examen_id' => 25, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 74: Anti-Mulleriana
            ['id' => 252, 'nombre' => 'ANTI- MULLERIANA', 'examen_id' => 74, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 37: Anti CCP (Péptido Cíclico Citrulinado)
            ['id' => 260, 'nombre' => 'ANTI CCP (PÉPTIDO CÍCLICO CITRULINADO)', 'examen_id' => 37, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 162: PROTEINA EN ORINA AL AZAR
            ['id' => 258, 'nombre' => 'PROTEINAS EN ORINA AL AZAR', 'examen_id' => 162, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 315, 'nombre' => 'CREATININA EN ORINA AL AZAR', 'examen_id' => 162, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 10: Anticoagulante Lupico (Cualitativo)
            ['id' => 263, 'nombre' => 'ANTICOAGULANTE LUPICO (CUALITATIVO)', 'examen_id' => 10, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 320, 'nombre' => 'DIMERO D', 'examen_id' => 10, 'estado' => 'inactivo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 165: RELACION ALBUMINA/ CREATININA EN ORINA AL AZAR
            ['id' => 264, 'nombre' => 'RELACION ALBUMINA/ CREATININA EN ORINA AL AZAR', 'examen_id' => 165, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 8: Espermograma
            ['id' => 265, 'nombre' => 'VOLUMEN', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 266, 'nombre' => 'ASPECTO', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 267, 'nombre' => 'LICUEFACCION', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 268, 'nombre' => 'VISCOSIDAD', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 269, 'nombre' => 'PH', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 2, 'tipo_conjunto' => null],
            ['id' => 270, 'nombre' => 'RECUENTO', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 271, 'nombre' => 'LEUCOCITOS', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 272, 'nombre' => 'HEMATIES', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 273, 'nombre' => 'CELULAS URETRALES', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 274, 'nombre' => 'CELULAS REDONDAS', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 275, 'nombre' => 'AGLUTINACION', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 1, 'tipo_conjunto' => null],
            ['id' => 276, 'nombre' => 'MORFOLOGIA NORMAL', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 7, 'tipo_conjunto' => null],
            ['id' => 278, 'nombre' => 'DOBLE COLA', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 8, 'tipo_conjunto' => null],
            ['id' => 279, 'nombre' => 'CABEZA EN GLOBO', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 8, 'tipo_conjunto' => null],
            ['id' => 280, 'nombre' => 'CABEZA DE ALFILER', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 8, 'tipo_conjunto' => null],
            ['id' => 281, 'nombre' => 'DOBLE CABEZA', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => 8, 'tipo_conjunto' => null],
            ['id' => 282, 'nombre' => 'GRADO 0 (INMOVILES), 1H, (1:1)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 283, 'nombre' => 'GRADO 0 (INMOVILES), 2H, (1:2)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 284, 'nombre' => 'GRADO 0 (INMOVILES), 3H, (1:3)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 285, 'nombre' => 'GRADO 0 (INMOVILES), 4H, (1:4)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 286, 'nombre' => 'GRADO 1 (UN SOLO LUGAR), 1H, (2:1)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 287, 'nombre' => 'GRADO 1 (UN SOLO LUGAR), 2H, (2:2)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 288, 'nombre' => 'GRADO 1 (UN SOLO LUGAR), 3H, (2:3)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 289, 'nombre' => 'GRADO 1 (UN SOLO LUGAR), 4H, (2:4)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 290, 'nombre' => 'GRADO 2 (ONDULADO LENTO), 1H, (3:1)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 291, 'nombre' => 'GRADO 2 (ONDULADO LENTO), 2H, (3:2)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 292, 'nombre' => 'GRADO 2 (ONDULADO LENTO), 3H, (3:3)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 293, 'nombre' => 'GRADO 2 (ONDULADO LENTO), 4H, (3:4)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 294, 'nombre' => 'GRADO 3 (VERTICAL RAPIDO), 1H, (4:1)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 295, 'nombre' => 'GRADO 3 (VERTICAL RAPIDO), 2H, (4:2)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 296, 'nombre' => 'GRADO 3 (VERTICAL RAPIDO), 3H, (4:3)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 297, 'nombre' => 'GRADO 3 (VERTICAL RAPIDO), 4H, (4:4)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 298, 'nombre' => 'VIABILIDAD, 1H, (5:1)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 299, 'nombre' => 'VIABILIDAD, 2H, (5:2)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 300, 'nombre' => 'VIABILIDAD, 3H, (5:3)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],
            ['id' => 301, 'nombre' => 'VIABILIDAD, 4H, (5:4)', 'examen_id' => 8, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => 'conjunto_69a8ea15de676'],

            // Examen 41: ESTRADIOL (E2)
            ['id' => 302, 'nombre' => 'ESTRADIOL', 'examen_id' => 41, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 130: FILTRADO GLOMERULAR
            ['id' => 303, 'nombre' => 'FILTRADO GLOMERULAR', 'examen_id' => 130, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 118: BILIRRUBINA INDIRECTA 
            ['id' => 304, 'nombre' => 'BILIRRUBINA INDIRECTA', 'examen_id' => 118, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 24: IgM TIFOIDEA (Salmonella typhi - Salmonella paratyphi)
            ['id' => 305, 'nombre' => 'IGM TIFOIDEA (SALMONELLA TYPHI - SALMONELLA PARATYPHI)', 'examen_id' => 24, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 19: Ag. Salmonella typhi 
            ['id' => 307, 'nombre' => 'AG. SALMONELLA TYPHI', 'examen_id' => 19, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 308, 'nombre' => 'AG. SALMONELLA PARATYPHI', 'examen_id' => 19, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 159: ALBUMINA EN ORINA AL AZAR
            ['id' => 309, 'nombre' => 'ALBUMINA EN ORINA AL AZAR', 'examen_id' => 159, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 39: CORTISOL AM
            ['id' => 310, 'nombre' => 'CORTISOL AM', 'examen_id' => 39, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 40: CORTISOL PM
            ['id' => 311, 'nombre' => 'CORTISOL PM', 'examen_id' => 40, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 42: FSH (HORMONA FOLÍCULO ESTIMULANTE)
            ['id' => 312, 'nombre' => 'FSH (HORMONA FOLÍCULO ESTIMULANTE)', 'examen_id' => 42, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 48: LH (HORMONA LUTEINIZANTE)
            ['id' => 313, 'nombre' => 'LH (HORMONA LUTEINIZANTE)', 'examen_id' => 48, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 36: ACTH (HORMONA ADRENOCORTICOTROPICA)
            ['id' => 314, 'nombre' => 'ACTH (HORMONA ADRENOCORTICOTROPICA)', 'examen_id' => 36, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 161: MICROALBUMINA EN ORINA AL AZAR
            ['id' => 316, 'nombre' => 'MICROALBUMINA EN ORINA AL AZAR', 'examen_id' => 161, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 90: HERPES IgM (TIPO II)
            ['id' => 317, 'nombre' => 'HERPES IGM (TIPO II)', 'examen_id' => 90, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 85: GONORREA – Ag.
            ['id' => 318, 'nombre' => 'GONORREA – AG.', 'examen_id' => 85, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 325, 'nombre' => 'GONORREA – AG.', 'examen_id' => 85, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 80: CHLAMYDIA TRACHOMATIS – Ag
            ['id' => 319, 'nombre' => 'CHLAMYDIA TRACHOMATIS – AG', 'examen_id' => 80, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
            ['id' => 326, 'nombre' => 'CHLAMYDIA TRACHOMATIS – AG.', 'examen_id' => 80, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 11: DIMERO-D
            ['id' => 321, 'nombre' => 'DIMERO-D', 'examen_id' => 11, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 120: CITOMEGALOVIRUS IGM
            ['id' => 323, 'nombre' => 'CITOMEGALOVIRUS IGM', 'examen_id' => 120, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 21: CONCENTRADO EN HECES
            ['id' => 322, 'nombre' => 'CONCENTRADO EN HECES', 'examen_id' => 21, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 2: COLORACION GRAM (FROTIS VAGINAL)
            ['id' => 324, 'nombre' => 'COLORACION GRAM (FROTIS VAGINAL)', 'examen_id' => 2, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 97: Procalcitonina
            ['id' => 327, 'nombre' => 'PROCALCITONINA', 'examen_id' => 97, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],

            // Examen 81: DEHIDROEPIANDROSTERONA SULFATO (DHEA-SO4)
            ['id' => 328, 'nombre' => 'DEHIDROEPIANDROSTERONA SULFATO (DHEA-SO4)', 'examen_id' => 81, 'estado' => 'activo', 'tipo_prueba_id' => null, 'tipo_conjunto' => null],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('pruebas')->upsert($chunk, ['id'], ['nombre', 'examen_id', 'estado', 'tipo_prueba_id', 'tipo_conjunto']);
        }
    }

    private function seedGruposEtarios(): void
    {
        $rows = [
            ['id' => 1, 'nombre' => 'EMBARAZO TEMPRANO', 'edad_min' => 0, 'edad_max' => 12, 'unidad_tiempo' => 'semanas', 'genero' => 'Femenino', 'estado' => 1],
            ['id' => 2, 'nombre' => 'EMBARAZO MEDIO', 'edad_min' => 13, 'edad_max' => 27, 'unidad_tiempo' => 'semanas', 'genero' => 'Femenino', 'estado' => 1],
            ['id' => 3, 'nombre' => 'EMBARAZO TARDÍO', 'edad_min' => 28, 'edad_max' => 42, 'unidad_tiempo' => 'semanas', 'genero' => 'Femenino', 'estado' => 1],
            ['id' => 4, 'nombre' => 'NEONATOS', 'edad_min' => 0, 'edad_max' => 28, 'unidad_tiempo' => 'días', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 5, 'nombre' => 'LACTANTES', 'edad_min' => 1, 'edad_max' => 12, 'unidad_tiempo' => 'meses', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 6, 'nombre' => 'NIÑOS', 'edad_min' => 1, 'edad_max' => 12, 'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 7, 'nombre' => 'ADOLESCENTES', 'edad_min' => 13, 'edad_max' => 17, 'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 8, 'nombre' => 'ADULTOS', 'edad_min' => 18, 'edad_max' => 64, 'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 9, 'nombre' => 'ADULTOS MAYORES', 'edad_min' => 65, 'edad_max' => 120, 'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 1],
            ['id' => 10, 'nombre' => 'TODAS LAS EDADES', 'edad_min' => 0, 'edad_max' => 120, 'unidad_tiempo' => 'años', 'genero' => 'Ambos', 'estado' => 1],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('grupos_etarios')->upsert($chunk, ['id'], ['nombre', 'edad_min', 'edad_max', 'unidad_tiempo', 'genero', 'estado']);
        }
    }

    private function seedValoresReferencia(): void
    {
        $rows = [
            ['id' => 1, 'prueba_id' => 227, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 0.0, 'valor_max' => 38.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 2, 'prueba_id' => 227, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Femenino', 'valor_min' => 0.0, 'valor_max' => 31.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 3, 'prueba_id' => 197, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 27.0, 'valor_max' => 100.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 5, 'prueba_id' => 194, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 230.0, 'valor_max' => 460.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 6, 'prueba_id' => 229, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 150.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 7, 'prueba_id' => 183, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE', 'genero' => 'Masculino', 'valor_min' => 3.4, 'valor_max' => 7.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 8, 'prueba_id' => 183, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER', 'genero' => 'Femenino', 'valor_min' => 2.4, 'valor_max' => 5.7, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 9, 'prueba_id' => 186, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.25, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 10, 'prueba_id' => 186, 'grupo_etario_id' => 4, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.3, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 11, 'prueba_id' => 186, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 12, 'prueba_id' => 186, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 13, 'prueba_id' => 186, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 14, 'prueba_id' => 187, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.1, 'valor_max' => 1.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 15, 'prueba_id' => 187, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.2, 'valor_max' => 1.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 16, 'prueba_id' => 187, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.2, 'valor_max' => 1.2, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 17, 'prueba_id' => 187, 'grupo_etario_id' => 4, 'operador' => '<=', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 5.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 18, 'prueba_id' => 190, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 190.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 19, 'prueba_id' => 190, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 170.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 20, 'prueba_id' => 190, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 170.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 21, 'prueba_id' => 188, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Riesgo Menor', 'genero' => 'Ambos', 'valor_min' => 55.0, 'valor_max' => null, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 22, 'prueba_id' => 188, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Riesgo Normal', 'genero' => 'Ambos', 'valor_min' => 35.0, 'valor_max' => 55.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 23, 'prueba_id' => 188, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Riesgo Elevado', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 35.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 26, 'prueba_id' => 181, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => null, 'valor_max' => 1.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 30, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 31, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 32, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 33, 'prueba_id' => 109, 'grupo_etario_id' => 8, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.3, 'valor_max' => 4.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 34, 'prueba_id' => 109, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.4, 'valor_max' => 4.0, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 35, 'prueba_id' => 109, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 8.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 36, 'prueba_id' => 109, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.6, 'valor_max' => 6.0, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 37, 'prueba_id' => 106, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5.1, 'valor_max' => 14.1, 'unidades' => 'ug/dL', 'nota' => null],
            ['id' => 38, 'prueba_id' => 106, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5.0, 'valor_max' => 12.0, 'unidades' => 'ug/dL', 'nota' => null],
            ['id' => 39, 'prueba_id' => 106, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5.0, 'valor_max' => 12.0, 'unidades' => 'ug/dL', 'nota' => null],
            ['id' => 40, 'prueba_id' => 245, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'PROBABILIDAD DE HIPERPLASIA BENIGNA', 'genero' => 'Masculino', 'valor_min' => 25.0, 'valor_max' => null, 'unidades' => '%', 'nota' => null],
            ['id' => 41, 'prueba_id' => 245, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'PROBABILIDAD MEDIANA DE NEOPLASIA PROSTÁTICA', 'genero' => 'Masculino', 'valor_min' => 11.0, 'valor_max' => 25.0, 'unidades' => '%', 'nota' => null],
            ['id' => 42, 'prueba_id' => 245, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'PROBABILIDAD ALTA DE NEOPLASIA PROSTÁTICA', 'genero' => 'Masculino', 'valor_min' => null, 'valor_max' => 11.0, 'unidades' => '%', 'nota' => null],
            ['id' => 43, 'prueba_id' => 105, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 2.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 44, 'prueba_id' => 105, 'grupo_etario_id' => 4, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 2.2, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 45, 'prueba_id' => 105, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 2.5, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 46, 'prueba_id' => 105, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 2.4, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 47, 'prueba_id' => 105, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 2.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 48, 'prueba_id' => 104, 'grupo_etario_id' => 8, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.0, 'valor_max' => 4.4, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 49, 'prueba_id' => 104, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.3, 'valor_max' => 4.2, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 50, 'prueba_id' => 104, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.3, 'valor_max' => 4.2, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 51, 'prueba_id' => 107, 'grupo_etario_id' => 8, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.98, 'valor_max' => 1.71, 'unidades' => 'ng/dL', 'nota' => null],
            ['id' => 52, 'prueba_id' => 107, 'grupo_etario_id' => 4, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 2.3, 'unidades' => 'ng/dL', 'nota' => null],
            ['id' => 53, 'prueba_id' => 107, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 1.8, 'unidades' => 'ng/dL', 'nota' => null],
            ['id' => 54, 'prueba_id' => 107, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 1.6, 'unidades' => 'ng/dL', 'nota' => null],
            ['id' => 55, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MENORES A 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 300.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 56, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MAYOR O IGUAL A 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 450.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 57, 'prueba_id' => 224, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 1.0, 'unidades' => 'mg/dL', 'nota' => 'LOS PACIENTES CON ELEVADAS CONCENTRACIONES DE HSCPR TIENEN UN RIESGO ELEVADO DE DESARROLLAR UN INFARTO MIOCARDICO Y UNA SEVERA ENFERMEDAD VASCULAR PERIFERICA.'],
            ['id' => 58, 'prueba_id' => 224, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 3.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 59, 'prueba_id' => 224, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 3.0, 'valor_max' => null, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 60, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 61, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 62, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 63, 'prueba_id' => 244, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => null, 'valor_max' => 1.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 64, 'prueba_id' => 199, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 60.0, 'valor_max' => 110.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 65, 'prueba_id' => 209, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 200.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 66, 'prueba_id' => 210, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 180.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 67, 'prueba_id' => 211, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 140.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 68, 'prueba_id' => 212, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 120.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 69, 'prueba_id' => 213, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 100.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 70, 'prueba_id' => 205, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 200.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 71, 'prueba_id' => 206, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 180.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 72, 'prueba_id' => 207, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 140.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 73, 'prueba_id' => 202, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 200.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 74, 'prueba_id' => 203, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 180.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 75, 'prueba_id' => 200, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 140.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 76, 'prueba_id' => 226, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 140.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 77, 'prueba_id' => 225, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 60.0, 'valor_max' => 110.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 78, 'prueba_id' => 208, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 60.0, 'valor_max' => 110.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 79, 'prueba_id' => 204, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 60.0, 'valor_max' => 110.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 80, 'prueba_id' => 201, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 60.0, 'valor_max' => 110.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 82, 'prueba_id' => 139, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 3800000.0, 'valor_max' => 5800000.0, 'unidades' => 'mm³', 'nota' => null],
            ['id' => 83, 'prueba_id' => 139, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 3700000.0, 'valor_max' => 5500000.0, 'unidades' => 'mm³', 'nota' => null],
            ['id' => 84, 'prueba_id' => 140, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 37.0, 'valor_max' => 53.0, 'unidades' => '%', 'nota' => null],
            ['id' => 85, 'prueba_id' => 140, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 35.0, 'valor_max' => 40.0, 'unidades' => '%', 'nota' => null],
            ['id' => 86, 'prueba_id' => 141, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 12.0, 'valor_max' => 17.0, 'unidades' => 'gr/dl', 'nota' => null],
            ['id' => 87, 'prueba_id' => 141, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 11.5, 'valor_max' => 13.5, 'unidades' => 'gr/dl', 'nota' => null],
            ['id' => 88, 'prueba_id' => 142, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 80.0, 'valor_max' => 110.0, 'unidades' => 'fL', 'nota' => null],
            ['id' => 89, 'prueba_id' => 142, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 80.0, 'valor_max' => 110.0, 'unidades' => 'fL', 'nota' => null],
            ['id' => 90, 'prueba_id' => 143, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 26.0, 'valor_max' => 38.0, 'unidades' => 'pg', 'nota' => null],
            ['id' => 92, 'prueba_id' => 144, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 31.0, 'valor_max' => 37.0, 'unidades' => 'gr/dl', 'nota' => null],
            ['id' => 93, 'prueba_id' => 144, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 31.0, 'valor_max' => 37.0, 'unidades' => 'gr/dl', 'nota' => null],
            ['id' => 94, 'prueba_id' => 145, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5000.0, 'valor_max' => 10000.0, 'unidades' => 'mm³', 'nota' => null],
            ['id' => 95, 'prueba_id' => 145, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5000.0, 'valor_max' => 15000.0, 'unidades' => 'mm³', 'nota' => null],
            ['id' => 96, 'prueba_id' => 146, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 50.0, 'valor_max' => 70.0, 'unidades' => '%', 'nota' => null],
            ['id' => 98, 'prueba_id' => 147, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 20.0, 'valor_max' => 40.0, 'unidades' => '%', 'nota' => null],
            ['id' => 99, 'prueba_id' => 147, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 20.0, 'valor_max' => 40.0, 'unidades' => '%', 'nota' => null],
            ['id' => 100, 'prueba_id' => 148, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 5.0, 'unidades' => '%', 'nota' => null],
            ['id' => 101, 'prueba_id' => 148, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 5.0, 'unidades' => '%', 'nota' => null],
            ['id' => 102, 'prueba_id' => 149, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.0, 'valor_max' => 8.0, 'unidades' => '%', 'nota' => null],
            ['id' => 103, 'prueba_id' => 149, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.0, 'valor_max' => 8.0, 'unidades' => '%', 'nota' => null],
            ['id' => 104, 'prueba_id' => 150, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 1.0, 'unidades' => '%', 'nota' => null],
            ['id' => 105, 'prueba_id' => 150, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 1.0, 'unidades' => '%', 'nota' => null],
            ['id' => 106, 'prueba_id' => 151, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 150000.0, 'valor_max' => 450000.0, 'unidades' => 'mm³', 'nota' => null],
            ['id' => 108, 'prueba_id' => 152, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 6.5, 'valor_max' => 11.0, 'unidades' => 'fL', 'nota' => null],
            ['id' => 109, 'prueba_id' => 152, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 6.5, 'valor_max' => 11.0, 'unidades' => 'fL', 'nota' => null],
            ['id' => 110, 'prueba_id' => 153, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => 14.0, 'unidades' => '%', 'nota' => null],
            ['id' => 111, 'prueba_id' => 153, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => 14.0, 'unidades' => '%', 'nota' => null],
            ['id' => 112, 'prueba_id' => 93, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 135.0, 'valor_max' => 148.0, 'unidades' => 'mmol/L', 'nota' => null],
            ['id' => 113, 'prueba_id' => 92, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 3.5, 'valor_max' => 5.3, 'unidades' => 'mmol/L', 'nota' => null],
            ['id' => 114, 'prueba_id' => 89, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 98.0, 'valor_max' => 107.0, 'unidades' => 'mmol/L', 'nota' => null],
            ['id' => 115, 'prueba_id' => 88, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 8.6, 'valor_max' => 11.0, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 116, 'prueba_id' => 91, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 1.6, 'valor_max' => 2.5, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 117, 'prueba_id' => 90, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.5, 'valor_max' => 4.5, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 118, 'prueba_id' => 234, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Femenino', 'valor_min' => 11.0, 'valor_max' => 20.0, 'unidades' => 'mg/Kg/24h', 'nota' => null],
            ['id' => 119, 'prueba_id' => 234, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 21.0, 'valor_max' => 26.0, 'unidades' => 'mg/Kg/24h', 'nota' => null],
            ['id' => 120, 'prueba_id' => 235, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE', 'genero' => 'Masculino', 'valor_min' => 0.7, 'valor_max' => 1.3, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 121, 'prueba_id' => 235, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER', 'genero' => 'Femenino', 'valor_min' => 0.6, 'valor_max' => 1.1, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 122, 'prueba_id' => 236, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 800.0, 'valor_max' => 2000.0, 'unidades' => 'ml', 'nota' => null],
            ['id' => 123, 'prueba_id' => 240, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 150.0, 'unidades' => 'mg/24 horas', 'nota' => null],
            ['id' => 124, 'prueba_id' => 113, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Femenino', 'valor_min' => 0.0, 'valor_max' => 15.0, 'unidades' => 'mm/Hora', 'nota' => null],
            ['id' => 125, 'prueba_id' => 113, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 0.0, 'valor_max' => 7.0, 'unidades' => 'mm/Hora', 'nota' => null],
            ['id' => 126, 'prueba_id' => 159, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 8.0, 'unidades' => 'UI/mL', 'nota' => null],
            ['id' => 127, 'prueba_id' => 171, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 6.0, 'unidades' => 'mg/L', 'nota' => null],
            ['id' => 128, 'prueba_id' => 125, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 200.0, 'unidades' => 'UI/mL', 'nota' => null],
            ['id' => 129, 'prueba_id' => 179, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 34.0, 'unidades' => 'U/mL', 'nota' => null],
            ['id' => 130, 'prueba_id' => 177, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 35.0, 'unidades' => 'U/mL', 'nota' => null],
            ['id' => 131, 'prueba_id' => 156, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.9, 'unidades' => 'COI', 'nota' => null],
            ['id' => 132, 'prueba_id' => 156, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Intermedio', 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 1.1, 'unidades' => 'COI', 'nota' => null],
            ['id' => 133, 'prueba_id' => 156, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.1, 'valor_max' => null, 'unidades' => 'COI', 'nota' => null],
            ['id' => 134, 'prueba_id' => 157, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.9, 'unidades' => 'COI', 'nota' => null],
            ['id' => 135, 'prueba_id' => 157, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Intermedio', 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 1.1, 'unidades' => 'COI', 'nota' => null],
            ['id' => 136, 'prueba_id' => 157, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.1, 'valor_max' => null, 'unidades' => 'COI', 'nota' => null],
            ['id' => 137, 'prueba_id' => 161, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.8, 'unidades' => 'U/mL', 'nota' => null],
            ['id' => 138, 'prueba_id' => 161, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Dudoso', 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 1.2, 'unidades' => 'U/mL', 'nota' => null],
            ['id' => 139, 'prueba_id' => 161, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.2, 'valor_max' => null, 'unidades' => 'U/mL', 'nota' => null],
            ['id' => 140, 'prueba_id' => 86, 'grupo_etario_id' => 10, 'operador' => '>=', 'descriptivo' => 'POSITIVO', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => null, 'unidades' => 'COI', 'nota' => null],
            ['id' => 141, 'prueba_id' => 86, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'NEGATIVO', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 1.0, 'unidades' => 'COI', 'nota' => null],
            ['id' => 142, 'prueba_id' => 164, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.9, 'unidades' => null, 'nota' => null],
            ['id' => 143, 'prueba_id' => 164, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Indeterminado', 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 1.0, 'unidades' => null, 'nota' => null],
            ['id' => 144, 'prueba_id' => 164, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => null, 'unidades' => null, 'nota' => null],
            ['id' => 145, 'prueba_id' => 163, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.9, 'unidades' => null, 'nota' => null],
            ['id' => 146, 'prueba_id' => 163, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Indeterminado', 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 1.0, 'unidades' => null, 'nota' => null],
            ['id' => 147, 'prueba_id' => 163, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => null, 'unidades' => null, 'nota' => null],
            ['id' => 148, 'prueba_id' => 162, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 1.5, 'unidades' => 'U', 'nota' => null],
            ['id' => 149, 'prueba_id' => 162, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Dudoso', 'genero' => 'Ambos', 'valor_min' => 1.51, 'valor_max' => 2.5, 'unidades' => 'U', 'nota' => null],
            ['id' => 150, 'prueba_id' => 162, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 2.5, 'valor_max' => null, 'unidades' => 'U', 'nota' => null],
            ['id' => 151, 'prueba_id' => 99, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.7, 'valor_max' => 24.8, 'unidades' => 'uIU/mL', 'nota' => null],
            ['id' => 152, 'prueba_id' => 184, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 3.5, 'valor_max' => 5.5, 'unidades' => 'g/dl', 'nota' => null],
            ['id' => 153, 'prueba_id' => 81, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 11.1, 'valor_max' => 14.3, 'unidades' => 'Segundos', 'nota' => null],
            ['id' => 154, 'prueba_id' => 82, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 80.0, 'valor_max' => 105.0, 'unidades' => '%', 'nota' => null],
            ['id' => 155, 'prueba_id' => 75, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 20.0, 'valor_max' => 33.0, 'unidades' => 'Segundos', 'nota' => null],
            ['id' => 156, 'prueba_id' => 70, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 180.0, 'valor_max' => 380.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 157, 'prueba_id' => 69, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 30.0, 'unidades' => 'Segundos', 'nota' => null],
            ['id' => 158, 'prueba_id' => 74, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 5.0, 'unidades' => 'Minutos', 'nota' => null],
            ['id' => 159, 'prueba_id' => 72, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5.0, 'valor_max' => 10.0, 'unidades' => 'Minutos', 'nota' => null],
            ['id' => 160, 'prueba_id' => 87, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 100.0, 'unidades' => 'ng/ml', 'nota' => null],
            ['id' => 161, 'prueba_id' => 87, 'grupo_etario_id' => 10, 'operador' => '>=', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 100.0, 'valor_max' => null, 'unidades' => 'ng/ml', 'nota' => null],
            ['id' => 162, 'prueba_id' => 248, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Insuficiencia', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 19.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 163, 'prueba_id' => 248, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Deficiencia', 'genero' => 'Ambos', 'valor_min' => 20.0, 'valor_max' => 29.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 164, 'prueba_id' => 248, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Optimo', 'genero' => 'Ambos', 'valor_min' => 30.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 165, 'prueba_id' => 248, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Toxicidad', 'genero' => 'Ambos', 'valor_min' => 150.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 166, 'prueba_id' => 249, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 0.3, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 167, 'prueba_id' => 173, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.8, 'unidades' => 'COI', 'nota' => null],
            ['id' => 168, 'prueba_id' => 173, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Indeterminado', 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 1.0, 'unidades' => 'COI', 'nota' => null],
            ['id' => 169, 'prueba_id' => 173, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => null, 'unidades' => 'COI', 'nota' => null],
            ['id' => 170, 'prueba_id' => 250, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Negativo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 1.0, 'unidades' => 'UI/ml', 'nota' => null],
            ['id' => 171, 'prueba_id' => 250, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Indeterminado', 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 3.0, 'unidades' => 'UI/ml', 'nota' => null],
            ['id' => 172, 'prueba_id' => 250, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Positivo', 'genero' => 'Ambos', 'valor_min' => 3.0, 'valor_max' => null, 'unidades' => 'UI/ml', 'nota' => null],
            ['id' => 173, 'prueba_id' => 220, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 6.5, 'valor_max' => 8.3, 'unidades' => 'g/dL', 'nota' => null],
            ['id' => 174, 'prueba_id' => 221, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 3.5, 'valor_max' => 5.5, 'unidades' => 'g/dl', 'nota' => null],
            ['id' => 175, 'prueba_id' => 222, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.0, 'valor_max' => 3.5, 'unidades' => 'g/dL', 'nota' => null],
            ['id' => 176, 'prueba_id' => 223, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 1.0, 'valor_max' => 2.0, 'unidades' => 'g/dL', 'nota' => null],
            ['id' => 177, 'prueba_id' => 191, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER ', 'genero' => 'Femenino', 'valor_min' => 26.0, 'valor_max' => 140.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 178, 'prueba_id' => 191, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE', 'genero' => 'Masculino', 'valor_min' => 38.0, 'valor_max' => 140.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 179, 'prueba_id' => 192, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 25.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 180, 'prueba_id' => 185, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 125.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 181, 'prueba_id' => 217, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 12.0, 'valor_max' => 70.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 182, 'prueba_id' => 195, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 24.0, 'valor_max' => 425.0, 'unidades' => 'ng/ml', 'nota' => null],
            ['id' => 183, 'prueba_id' => 195, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Femenino', 'valor_min' => 13.0, 'valor_max' => 150.0, 'unidades' => 'ng/ml', 'nota' => null],
            ['id' => 184, 'prueba_id' => 228, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => null, 'valor_max' => 40.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 185, 'prueba_id' => 228, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Femenino', 'valor_min' => null, 'valor_max' => 32.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 186, 'prueba_id' => 218, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => 50.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 187, 'prueba_id' => 219, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 7.0, 'valor_max' => 24.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 188, 'prueba_id' => 180, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NO FUMADORES', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 5.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 189, 'prueba_id' => 180, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'FUMADORES', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 190, 'prueba_id' => 214, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'NO DIABETICO', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 5.7, 'unidades' => '%', 'nota' => null],
            ['id' => 191, 'prueba_id' => 214, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'DIABETICO CONTROLADO', 'genero' => 'Ambos', 'valor_min' => 5.7, 'valor_max' => 6.5, 'unidades' => '%', 'nota' => null],
            ['id' => 192, 'prueba_id' => 214, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'DIABETICO MAL CONTROLADO', 'genero' => 'Ambos', 'valor_min' => 6.5, 'valor_max' => null, 'unidades' => '%', 'nota' => null],
            ['id' => 193, 'prueba_id' => 252, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJERES MENORES DE 20a', 'genero' => 'Ambos', 'valor_min' => 0.47, 'valor_max' => 9.11, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 194, 'prueba_id' => 252, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJERES MENORES DE 30a', 'genero' => 'Ambos', 'valor_min' => 0.49, 'valor_max' => 8.91, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 195, 'prueba_id' => 252, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJERES DE 30-39 a', 'genero' => 'Ambos', 'valor_min' => 0.31, 'valor_max' => 7.86, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 196, 'prueba_id' => 252, 'grupo_etario_id' => 10, 'operador' => '<=', 'descriptivo' => 'MUJERES DE 40-50 a', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 5.07, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 197, 'prueba_id' => 251, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 6.0, 'valor_max' => 7.0, 'unidades' => null, 'nota' => 'pH ácido: Un pH menor de 6.0 es evidencia sugestiva de mala absorción de azúcares en niños y algunos adultos. 
pH alcalino: Un pH mayor de 7.0 indica trastornos digestivos con aumento de la flora proteolítica '],
            ['id' => 198, 'prueba_id' => 193, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE', 'genero' => 'Masculino', 'valor_min' => 0.7, 'valor_max' => 1.3, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 199, 'prueba_id' => 193, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER', 'genero' => 'Femenino', 'valor_min' => 0.6, 'valor_max' => 1.1, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 201, 'prueba_id' => 190, 'grupo_etario_id' => 9, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 190.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 202, 'prueba_id' => 193, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => 'NIÑOS', 'genero' => 'Ambos', 'valor_min' => 0.5, 'valor_max' => 1.0, 'unidades' => 'mg/dl', 'nota' => null],
            ['id' => 203, 'prueba_id' => 104, 'grupo_etario_id' => 9, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.0, 'valor_max' => 4.4, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 204, 'prueba_id' => 107, 'grupo_etario_id' => 9, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.98, 'valor_max' => 1.71, 'unidades' => 'ng/dL', 'nota' => null],
            ['id' => 205, 'prueba_id' => 109, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.3, 'valor_max' => 4.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 206, 'prueba_id' => 258, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 15.0, 'unidades' => 'mg/L', 'nota' => null],
            ['id' => 207, 'prueba_id' => 189, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 150.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 208, 'prueba_id' => 105, 'grupo_etario_id' => 9, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 2.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 209, 'prueba_id' => 106, 'grupo_etario_id' => 9, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 5.1, 'valor_max' => 14.1, 'unidades' => 'ug/dL', 'nota' => null],
            ['id' => 210, 'prueba_id' => 233, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER', 'genero' => 'Femenino', 'valor_min' => 88.0, 'valor_max' => 128.0, 'unidades' => 'mL/min', 'nota' => null],
            ['id' => 211, 'prueba_id' => 233, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE ', 'genero' => 'Masculino', 'valor_min' => 97.0, 'valor_max' => 137.0, 'unidades' => 'mL/min', 'nota' => null],
            ['id' => 212, 'prueba_id' => 100, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 15.0, 'valor_max' => 180.0, 'unidades' => 'uIU/mL', 'nota' => null],
            ['id' => 213, 'prueba_id' => 108, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Hombres', 'genero' => 'Masculino', 'valor_min' => 1.71, 'valor_max' => 7.87, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 214, 'prueba_id' => 108, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Mujeres', 'genero' => 'Femenino', 'valor_min' => 0.06, 'valor_max' => 0.82, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 215, 'prueba_id' => 108, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => 'Niños hasta 1 año', 'genero' => 'Ambos', 'valor_min' => 0.12, 'valor_max' => 0.21, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 216, 'prueba_id' => 108, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => 'Niños 1 a 6 años', 'genero' => 'Ambos', 'valor_min' => 0.03, 'valor_max' => 0.32, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 217, 'prueba_id' => 108, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => 'Niños de 7 a 12 años', 'genero' => 'Ambos', 'valor_min' => 0.03, 'valor_max' => 0.68, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 218, 'prueba_id' => 108, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => 'Niños de 13 a 17 años', 'genero' => 'Ambos', 'valor_min' => 0.28, 'valor_max' => 11.1, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 219, 'prueba_id' => 103, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Hombres', 'genero' => 'Masculino', 'valor_min' => 2.52, 'valor_max' => 13.23, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 220, 'prueba_id' => 103, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Mujeres: Pre-menopausea', 'genero' => 'Femenino', 'valor_min' => 3.27, 'valor_max' => 26.81, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 221, 'prueba_id' => 103, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Mujeres: Post-menopausea', 'genero' => 'Femenino', 'valor_min' => 2.68, 'valor_max' => 19.72, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 222, 'prueba_id' => 264, 'grupo_etario_id' => 10, 'operador' => '<=', 'descriptivo' => 'Normal', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 30.0, 'unidades' => 'mg/g', 'nota' => null],
            ['id' => 223, 'prueba_id' => 264, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Microalbuminuria', 'genero' => 'Ambos', 'valor_min' => 30.0, 'valor_max' => 300.0, 'unidades' => 'mg/g', 'nota' => null],
            ['id' => 224, 'prueba_id' => 264, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Macroalbuminuria', 'genero' => 'Ambos', 'valor_min' => 300.0, 'valor_max' => null, 'unidades' => 'mg/g', 'nota' => null],
            ['id' => 225, 'prueba_id' => 265, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 2.0, 'valor_max' => 6.0, 'unidades' => 'mL', 'nota' => null],
            ['id' => 226, 'prueba_id' => 269, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Masculino', 'valor_min' => 7.2, 'valor_max' => 7.8, 'unidades' => null, 'nota' => null],
            ['id' => 227, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'Mujer no embarazada', 'genero' => 'Femenino', 'valor_min' => 0.0, 'valor_max' => 5.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 228, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '3 - 7 dias', 'genero' => 'Femenino', 'valor_min' => 5.0, 'valor_max' => 50.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 229, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '1 - 2 semanas', 'genero' => 'Femenino', 'valor_min' => 10.0, 'valor_max' => 472.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 230, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '2 - 3 semanas', 'genero' => 'Femenino', 'valor_min' => 90.0, 'valor_max' => 4590.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 231, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '3 - 4 semanas', 'genero' => 'Femenino', 'valor_min' => 462.0, 'valor_max' => 10940.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 232, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '4 - 5 semanas', 'genero' => 'Femenino', 'valor_min' => 1065.0, 'valor_max' => 68248.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 233, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '5 - 6 semanas', 'genero' => 'Femenino', 'valor_min' => 7458.0, 'valor_max' => 118515.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 234, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '6 - 7 semanas', 'genero' => 'Femenino', 'valor_min' => 14423.0, 'valor_max' => 175638.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 235, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '7 - 8 semanas', 'genero' => 'Femenino', 'valor_min' => 31510.0, 'valor_max' => 184628.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 236, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '8 - 12 semanas', 'genero' => 'Femenino', 'valor_min' => 28639.0, 'valor_max' => 224919.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 237, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '12 - 16 semanas', 'genero' => 'Femenino', 'valor_min' => 9870.0, 'valor_max' => 106917.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 238, 'prueba_id' => 94, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '16 - 18 semanas', 'genero' => 'Femenino', 'valor_min' => 7924.0, 'valor_max' => 56552.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 239, 'prueba_id' => 303, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 90.0, 'valor_max' => 120.0, 'unidades' => 'mL/min', 'nota' => null],
            ['id' => 240, 'prueba_id' => 304, 'grupo_etario_id' => 8, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.2, 'valor_max' => 1.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 241, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '< 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 300.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 242, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => '≥ 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 450.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 243, 'prueba_id' => 178, 'grupo_etario_id' => 10, 'operador' => '<=', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 25.0, 'unidades' => 'U/ml', 'nota' => null],
            ['id' => 244, 'prueba_id' => 198, 'grupo_etario_id' => 8, 'operador' => '<=', 'descriptivo' => 'HOMBRES', 'genero' => 'Masculino', 'valor_min' => null, 'valor_max' => 55.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 245, 'prueba_id' => 198, 'grupo_etario_id' => 8, 'operador' => '<=', 'descriptivo' => 'MUJERES ', 'genero' => 'Femenino', 'valor_min' => null, 'valor_max' => 38.0, 'unidades' => 'U/L', 'nota' => null],
            ['id' => 246, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Normal o Bajo Riesgo', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 247, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Mediano Riesgo', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 248, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'Alto Riesgo', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 249, 'prueba_id' => 309, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 15.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 251, 'prueba_id' => 310, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 45.5, 'valor_max' => 208.2, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 252, 'prueba_id' => 311, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 2.3, 'valor_max' => 11.9, 'unidades' => 'ug/dL', 'nota' => null],
            ['id' => 253, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Hombres', 'genero' => 'Ambos', 'valor_min' => 0.7, 'valor_max' => 12.4, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 254, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Niños 0 a 3 años', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 10.0, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 255, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Niños 4 a 9 años', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 1.8, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 256, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Folicular', 'genero' => 'Ambos', 'valor_min' => 3.5, 'valor_max' => 12.5, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 257, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Folicular dia 2-3', 'genero' => 'Ambos', 'valor_min' => 3.0, 'valor_max' => 14.4, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 258, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Ovulación +- 3 dias', 'genero' => 'Ambos', 'valor_min' => 4.7, 'valor_max' => 21.5, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 259, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Lutea', 'genero' => 'Ambos', 'valor_min' => 1.7, 'valor_max' => 7.7, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 260, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Post Menopausia', 'genero' => 'Ambos', 'valor_min' => 25.8, 'valor_max' => 134.8, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 261, 'prueba_id' => 312, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Anticonceptivos Orales', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.9, 'unidades' => 'mUI/mL', 'nota' => null],
            ['id' => 262, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Hombres', 'genero' => 'Ambos', 'valor_min' => 0.5, 'valor_max' => 12.43, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 263, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Niños 0 - 9 años', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 3.7, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 264, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Folicular', 'genero' => 'Ambos', 'valor_min' => 1.6, 'valor_max' => 12.18, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 265, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Ovulación + - 3 dias', 'genero' => 'Ambos', 'valor_min' => 14.0, 'valor_max' => 95.6, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 266, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Lútea', 'genero' => 'Ambos', 'valor_min' => 0.5, 'valor_max' => 15.1, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 267, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Perimenstrual + - 8 dias', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 12.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 269, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Post Menopausia', 'genero' => 'Ambos', 'valor_min' => 5.04, 'valor_max' => 63.11, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 270, 'prueba_id' => 101, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Anticonceptivos Orales', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 8.0, 'unidades' => 'mIU/mL', 'nota' => null],
            ['id' => 271, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Hombres', 'genero' => 'Ambos', 'valor_min' => 7.63, 'valor_max' => 42.6, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 272, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Folicular', 'genero' => 'Ambos', 'valor_min' => 12.5, 'valor_max' => 166.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 273, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Ovulación +- 3 dias', 'genero' => 'Ambos', 'valor_min' => 85.8, 'valor_max' => 498.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 274, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Fase Lútea', 'genero' => 'Ambos', 'valor_min' => 43.8, 'valor_max' => 211.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 275, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Postmenopausia', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 54.7, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 276, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Gestación 1er trimestre', 'genero' => 'Ambos', 'valor_min' => 215.0, 'valor_max' => 4300.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 277, 'prueba_id' => 302, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Niños 1 a 10 años', 'genero' => 'Ambos', 'valor_min' => 5.0, 'valor_max' => 27.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 278, 'prueba_id' => 314, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 7.2, 'valor_max' => 63.3, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 279, 'prueba_id' => 234, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MUJER', 'genero' => 'Femenino', 'valor_min' => 11.0, 'valor_max' => 20.0, 'unidades' => 'mg/Kg/24h', 'nota' => null],
            ['id' => 280, 'prueba_id' => 234, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'HOMBRE', 'genero' => 'Masculino', 'valor_min' => 21.0, 'valor_max' => 26.0, 'unidades' => 'mg/Kg/24h', 'nota' => null],
            ['id' => 281, 'prueba_id' => 316, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 1.5, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 282, 'prueba_id' => 304, 'grupo_etario_id' => 4, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.1, 'valor_max' => 1.0, 'unidades' => 'mg/dL', 'nota' => null],
            ['id' => 283, 'prueba_id' => 113, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 20.0, 'unidades' => 'mm/Hora', 'nota' => null],
            ['id' => 284, 'prueba_id' => 109, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.3, 'valor_max' => 4.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 285, 'prueba_id' => 109, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.4, 'valor_max' => 4.0, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 286, 'prueba_id' => 109, 'grupo_etario_id' => 5, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.8, 'valor_max' => 8.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 287, 'prueba_id' => 109, 'grupo_etario_id' => 6, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.6, 'valor_max' => 6.0, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 288, 'prueba_id' => 109, 'grupo_etario_id' => 8, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.3, 'valor_max' => 4.2, 'unidades' => 'uUI/mL', 'nota' => null],
            ['id' => 289, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 290, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 291, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 292, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 293, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 294, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 295, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MENORES A 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 300.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 296, 'prueba_id' => 246, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MAYOR O IGUAL A 75 AÑOS', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 450.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 297, 'prueba_id' => 69, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 30.0, 'unidades' => 'Segundos', 'nota' => null],
            ['id' => 298, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 299, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 300, 'prueba_id' => 182, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 301, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'NORMAL O BAJO RIESGO', 'genero' => 'Ambos', 'valor_min' => 0.0, 'valor_max' => 4.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 302, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'MEDIANO RIESGO', 'genero' => 'Ambos', 'valor_min' => 4.0, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 303, 'prueba_id' => 243, 'grupo_etario_id' => 10, 'operador' => '>', 'descriptivo' => 'ALTO RIESGO', 'genero' => 'Ambos', 'valor_min' => 10.0, 'valor_max' => null, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 304, 'prueba_id' => 98, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 15.0, 'valor_max' => 65.0, 'unidades' => 'pg/mL', 'nota' => null],
            ['id' => 305, 'prueba_id' => 176, 'grupo_etario_id' => 10, 'operador' => '<=', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 10.0, 'unidades' => 'ng/mL', 'nota' => null],
            ['id' => 306, 'prueba_id' => 193, 'grupo_etario_id' => 7, 'operador' => 'rango', 'descriptivo' => null, 'genero' => 'Ambos', 'valor_min' => 0.5, 'valor_max' => 1.0, 'unidades' => null, 'nota' => null],
            ['id' => 307, 'prueba_id' => 323, 'grupo_etario_id' => 10, 'operador' => '<', 'descriptivo' => 'No Reactivo', 'genero' => 'Ambos', 'valor_min' => null, 'valor_max' => 0.9, 'unidades' => 'RLU', 'nota' => null],
            ['id' => 308, 'prueba_id' => 323, 'grupo_etario_id' => 10, 'operador' => 'rango', 'descriptivo' => 'Indeterminado', 'genero' => 'Ambos', 'valor_min' => 0.9, 'valor_max' => 1.1, 'unidades' => 'RLU', 'nota' => null],
            ['id' => 309, 'prueba_id' => 323, 'grupo_etario_id' => 10, 'operador' => '>=', 'descriptivo' => 'Reactivo', 'genero' => 'Ambos', 'valor_min' => 1.1, 'valor_max' => null, 'unidades' => 'RLU', 'nota' => null],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('valor_referencias')->upsert($chunk, ['id'], ['prueba_id', 'grupo_etario_id', 'operador', 'descriptivo', 'genero', 'valor_min', 'valor_max', 'unidades', 'nota']);
        }
    }

    private function seedPerfiles(): void
    {
        $rows = [
            ['id' => 1, 'nombre' => 'PERFIL HEPÁTICO', 'precio' => 35.0, 'estado' => 1],
            ['id' => 2, 'nombre' => 'PERFIL DE RUTINA', 'precio' => 25.0, 'estado' => 1],
            ['id' => 3, 'nombre' => 'PERFIL RENAL', 'precio' => 30.0, 'estado' => 1],
            ['id' => 4, 'nombre' => 'PERFIL PRENATAL', 'precio' => 50.0, 'estado' => 1],
            ['id' => 5, 'nombre' => 'PERFIL TIROIDEO TOTAL', 'precio' => 25.0, 'estado' => 1],
            ['id' => 6, 'nombre' => 'PERFIL TIROIDEO LIBRE', 'precio' => 35.0, 'estado' => 1],
            ['id' => 7, 'nombre' => 'PERFIL ELECTROLITOS', 'precio' => 48.0, 'estado' => 1],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('perfil')->upsert($chunk, ['id'], ['nombre', 'precio', 'estado']);
        }
    }

    private function seedDetallePerfiles(): void
    {
        $rows = [
            ['id' => 1, 'perfil_id' => 1, 'examen_id' => 117],
            ['id' => 2, 'perfil_id' => 1, 'examen_id' => 119],
            ['id' => 3, 'perfil_id' => 1, 'examen_id' => 132],
            ['id' => 4, 'perfil_id' => 1, 'examen_id' => 147],
            ['id' => 5, 'perfil_id' => 1, 'examen_id' => 148],
            ['id' => 6, 'perfil_id' => 2, 'examen_id' => 22],
            ['id' => 7, 'perfil_id' => 2, 'examen_id' => 160],
            ['id' => 8, 'perfil_id' => 2, 'examen_id' => 65],
            ['id' => 9, 'perfil_id' => 2, 'examen_id' => 114],
            ['id' => 10, 'perfil_id' => 2, 'examen_id' => 124],
            ['id' => 11, 'perfil_id' => 2, 'examen_id' => 127],
            ['id' => 12, 'perfil_id' => 2, 'examen_id' => 134],
            ['id' => 13, 'perfil_id' => 2, 'examen_id' => 143],
            ['id' => 14, 'perfil_id' => 2, 'examen_id' => 149],
            ['id' => 15, 'perfil_id' => 3, 'examen_id' => 33],
            ['id' => 16, 'perfil_id' => 3, 'examen_id' => 34],
            ['id' => 17, 'perfil_id' => 3, 'examen_id' => 65],
            ['id' => 18, 'perfil_id' => 3, 'examen_id' => 114],
            ['id' => 19, 'perfil_id' => 3, 'examen_id' => 127],
            ['id' => 20, 'perfil_id' => 3, 'examen_id' => 143],
            ['id' => 21, 'perfil_id' => 3, 'examen_id' => 160],
            ['id' => 22, 'perfil_id' => 4, 'examen_id' => 65],
            ['id' => 23, 'perfil_id' => 4, 'examen_id' => 103],
            ['id' => 24, 'perfil_id' => 4, 'examen_id' => 100],
            ['id' => 25, 'perfil_id' => 4, 'examen_id' => 102],
            ['id' => 26, 'perfil_id' => 4, 'examen_id' => 105],
            ['id' => 27, 'perfil_id' => 4, 'examen_id' => 134],
            ['id' => 28, 'perfil_id' => 4, 'examen_id' => 160],
            ['id' => 29, 'perfil_id' => 5, 'examen_id' => 52],
            ['id' => 30, 'perfil_id' => 5, 'examen_id' => 54],
            ['id' => 31, 'perfil_id' => 5, 'examen_id' => 57],
            ['id' => 32, 'perfil_id' => 6, 'examen_id' => 51],
            ['id' => 33, 'perfil_id' => 6, 'examen_id' => 53],
            ['id' => 34, 'perfil_id' => 6, 'examen_id' => 57],
            ['id' => 35, 'perfil_id' => 7, 'examen_id' => 29],
            ['id' => 36, 'perfil_id' => 7, 'examen_id' => 30],
            ['id' => 37, 'perfil_id' => 7, 'examen_id' => 31],
            ['id' => 38, 'perfil_id' => 7, 'examen_id' => 32],
            ['id' => 39, 'perfil_id' => 7, 'examen_id' => 33],
            ['id' => 40, 'perfil_id' => 7, 'examen_id' => 34],
        ];

        foreach (array_chunk($rows, 250) as $chunk) {
            DB::table('detalle_perfil')->upsert($chunk, ['id'], ['perfil_id', 'examen_id']);
        }
    }

}
