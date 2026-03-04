<?php

namespace Database\Seeders;

use App\Models\Block;
use App\Models\Lot;
use App\Models\Project;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create Tenant
        $tenant = Tenant::create([
            'name' => 'Constructora La Cumbre',
            'slug' => 'constructora-la-cumbre',
            'company_name' => 'Constructora La Cumbre S.A.S.',
            'nit' => '900.123.456-7',
            'phone' => '+57 310 123 4567',
            'email' => 'info@lacumbre.com',
            'address' => 'Calle 10 #5-30',
            'city' => 'Flandes',
            'department' => 'Tolima',
            'is_active' => true,
        ]);

        // Create Admin User
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@lacumbre.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        // Create Sales Agent User
        User::create([
            'name' => 'Agente Ventas',
            'email' => 'ventas@lacumbre.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'sales_agent',
            'email_verified_at' => now(),
        ]);

        // Create Accountant User
        User::create([
            'name' => 'Contador',
            'email' => 'contador@lacumbre.com',
            'password' => bcrypt('password'),
            'tenant_id' => $tenant->id,
            'role' => 'accountant',
            'email_verified_at' => now(),
        ]);

        // Create "Proyecto La Cumbre"
        $project = Project::create([
            'tenant_id' => $tenant->id,
            'name' => 'Proyecto La Cumbre',
            'slug' => 'proyecto-la-cumbre',
            'description' => 'Proyecto de lotes campestres ubicado en la zona rural del municipio de Flandes, Tolima. Área total de 7 hectáreas (70.000 m²) con excelente ubicación sobre doble calzada.',
            'location' => 'Zona Rural, Municipio de Flandes',
            'municipality' => 'Flandes',
            'department' => 'Tolima',
            'total_area' => 70000,
            'area_unit' => 'm²',
            'price_per_m2' => 180000,
            'status' => 'active',
        ]);

        // Define 24 blocks (Manzanas) with their lots based on planimetry
        // Mix of lot sizes: 91m², 100m², 120m², 144m², 160m², 200m², 250m²
        $blockDefinitions = [
            'MZ1' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '8', 'area' => 144, 'front' => 12, 'depth' => 12],
            ],
            'MZ2' => [
                ['num' => '1', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '2', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '3', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '4', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '5', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '6', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '7', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '8', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '9', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '10', 'area' => 144, 'front' => 12, 'depth' => 12],
            ],
            'MZ3' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '10', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '11', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '12', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ4' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '8', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ5' => [
                ['num' => '1', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '2', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '3', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '4', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '5', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '6', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ6' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '10', 'area' => 120, 'front' => 10, 'depth' => 12],
            ],
            'MZ7' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '8', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '9', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '10', 'area' => 120, 'front' => 10, 'depth' => 12],
            ],
            'MZ8' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '10', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ9' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '8', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ10' => [
                ['num' => '1', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '2', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '3', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '4', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '5', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '6', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '7', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '8', 'area' => 120, 'front' => 10, 'depth' => 12],
            ],
            'MZ11' => [
                ['num' => '1', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '2', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '3', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '4', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '5', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '6', 'area' => 250, 'front' => 12.5, 'depth' => 20],
            ],
            'MZ12' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '10', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '11', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '12', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ13' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '8', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '9', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '10', 'area' => 160, 'front' => 10, 'depth' => 16],
            ],
            'MZ14' => [
                ['num' => '1', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '2', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '3', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '4', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '5', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '6', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '7', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '8', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '9', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '10', 'area' => 120, 'front' => 10, 'depth' => 12],
            ],
            'MZ15' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ16' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '8', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '9', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '10', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ17' => [
                ['num' => '1', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '2', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '3', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '4', 'area' => 250, 'front' => 12.5, 'depth' => 20],
            ],
            'MZ18' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '10', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ19' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '8', 'area' => 120, 'front' => 10, 'depth' => 12],
            ],
            'MZ20' => [
                ['num' => '1', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '2', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '3', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '4', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '5', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '6', 'area' => 120, 'front' => 10, 'depth' => 12],
                ['num' => '7', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '8', 'area' => 160, 'front' => 10, 'depth' => 16],
            ],
            'MZ21' => [
                ['num' => '1', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '2', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '3', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '4', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '5', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '6', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '7', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '8', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '9', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '10', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '11', 'area' => 91, 'front' => 7, 'depth' => 13],
                ['num' => '12', 'area' => 91, 'front' => 7, 'depth' => 13],
            ],
            'MZ22' => [
                ['num' => '1', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '2', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '3', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '4', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '5', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '6', 'area' => 144, 'front' => 12, 'depth' => 12],
                ['num' => '7', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '8', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ23' => [
                ['num' => '1', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '2', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '3', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '4', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '5', 'area' => 200, 'front' => 10, 'depth' => 20],
                ['num' => '6', 'area' => 200, 'front' => 10, 'depth' => 20],
            ],
            'MZ24' => [
                ['num' => '1', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '2', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '3', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '4', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '5', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '6', 'area' => 160, 'front' => 10, 'depth' => 16],
                ['num' => '7', 'area' => 250, 'front' => 12.5, 'depth' => 20],
                ['num' => '8', 'area' => 250, 'front' => 12.5, 'depth' => 20],
            ],
        ];

        $pricePerM2 = 180000; // COP per m²

        foreach ($blockDefinitions as $blockName => $lots) {
            $block = Block::create([
                'project_id' => $project->id,
                'name' => $blockName,
                'code' => $blockName,
                'total_lots' => count($lots),
            ]);

            foreach ($lots as $lotDef) {
                Lot::create([
                    'block_id' => $block->id,
                    'lot_number' => $lotDef['num'],
                    'area' => $lotDef['area'],
                    'price' => $lotDef['area'] * $pricePerM2,
                    'front_length' => $lotDef['front'],
                    'depth_length' => $lotDef['depth'],
                    'status' => 'available',
                ]);
            }
        }
    }
}
