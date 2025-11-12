<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();

            // --- Relaciones ---
            $table->foreignId('user_id')
                ->constrained('users') // 'constrained' asume que la tabla es 'users' y la columna 'id'
                ->onDelete('cascade'); // Si se borra el usuario, se borran sus tickets

            // El TÉCNICO asignado
            $table->foreignId('tecnico_id')
                ->nullable()
                ->constrained('users') // También apunta a la tabla 'users'
                ->onDelete('set null'); // Si se borra el técnico, el ticket queda "sin asignar" (null)

            // --- Detalles del Dispositivo ---
            $table->string('tipo_dispositivo');
            $table->string('marca');
            $table->string('modelo');
            $table->string('numero_serie')->nullable();
            $table->text('descripcion_problema');
            // --- Estados
            $table->enum('estado_usuario', ['pendiente', 'en_revision', 'reparado', 'cerrado'])->default('pendiente');
            $table->enum('estado_interno', ['sin_iniciar', 'en_proceso', 'completado'])->default('sin_iniciar');
            $table->enum('prioridad', ['baja', 'media', 'alta'])->default('baja');

            $table->timestamps(); // (created_at y updated_at)
        });

    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
