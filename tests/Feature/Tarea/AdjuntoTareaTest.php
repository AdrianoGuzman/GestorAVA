<?php

namespace Tests\Feature\Tarea;

use App\Enums\CategoriaAdjunto;
use App\Enums\EstadoTarea;
use App\Enums\NivelJerarquico;
use App\Enums\TipoEvento;
use App\Models\Tarea;
use App\Models\UnidadOrganizacional;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\RefreshesDualSchemaDatabase;
use Tests\TestCase;

class AdjuntoTareaTest extends TestCase
{
    use RefreshesDualSchemaDatabase;

    private function usuario(NivelJerarquico $nivel, ?UnidadOrganizacional $unidad = null): User
    {
        return User::factory()->conNivel($nivel, $unidad)->create();
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake("local");
    }

    public function test_el_responsable_puede_adjuntar_un_archivo(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertRedirect()->assertSessionHas("success");

        $tarea->refresh();
        $this->assertCount(1, $tarea->adjuntos);
        $this->assertSame("evidencia.pdf", $tarea->adjuntos->first()->nombre_original);
        $this->assertSame(CategoriaAdjunto::Evidencia, $tarea->adjuntos->first()->categoria);
        Storage::disk("local")->assertExists($tarea->adjuntos->first()->ruta);

        $this->assertTrue($tarea->historial()->where("tipo_evento", TipoEvento::AdjuntoAgregado)->exists());
    }

    public function test_se_puede_subir_como_necesario_para_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("plano.pdf", 100, "application/pdf");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "necesario",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertSame(CategoriaAdjunto::Necesario, $tarea->fresh()->adjuntos->first()->categoria);
    }

    public function test_la_categoria_es_obligatoria_y_valida(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "otra-cosa",
        ])->assertSessionHasErrors("categoria");

        $this->assertCount(0, $tarea->fresh()->adjuntos);
    }

    public function test_se_puede_subir_un_zip(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("paquete.zip", 100, "application/zip");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertCount(1, $tarea->fresh()->adjuntos);
    }

    public function test_un_colaborador_puede_adjuntar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $colaborador = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tarea->colaboradores()->attach($colaborador->id);

        $archivo = UploadedFile::fake()->image("foto.jpg");

        $this->actingAs($colaborador)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertRedirect()->assertSessionHas("success");

        $this->assertCount(1, $tarea->fresh()->adjuntos);
    }

    public function test_un_usuario_ajeno_no_puede_adjuntar(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $ajeno = $this->usuario(NivelJerarquico::JefeArea, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");

        $this->actingAs($ajeno)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertSessionHas("error");

        $this->assertCount(0, $tarea->fresh()->adjuntos);
    }

    public function test_rechaza_formatos_no_permitidos(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("script.exe", 100, "application/x-msdownload");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertSessionHasErrors("archivo");

        $this->assertCount(0, $tarea->fresh()->adjuntos);
    }

    public function test_rechaza_archivos_mas_grandes_que_el_limite(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("grande.pdf", 10241, "application/pdf");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertSessionHasErrors("archivo");

        $this->assertCount(0, $tarea->fresh()->adjuntos);
    }

    public function test_se_puede_descargar_un_adjunto_de_la_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");
        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", ["archivo" => $archivo, "categoria" => "evidencia"]);

        $adjunto = $tarea->fresh()->adjuntos->first();

        $this->actingAs($responsable)
            ->get("/tareas/{$tarea->id}/adjuntos/{$adjunto->id}/descargar")
            ->assertOk();
    }

    public function test_no_se_puede_descargar_un_adjunto_de_otra_tarea(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tareaA = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tareaB = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");
        $this->actingAs($responsable)->post("/tareas/{$tareaA->id}/adjuntos", ["archivo" => $archivo, "categoria" => "evidencia"]);
        $adjunto = $tareaA->fresh()->adjuntos->first();

        $this->actingAs($responsable)
            ->get("/tareas/{$tareaB->id}/adjuntos/{$adjunto->id}/descargar")
            ->assertNotFound();
    }

    public function test_la_evidencia_de_una_tarea_hija_aparece_en_la_tarea_padre(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsablePadre = $this->usuario(NivelJerarquico::Asistente, $obra);
        $responsableHija = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tareaPadre = Tarea::factory()->create([
            "responsable_id" => $responsablePadre->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tareaHija = Tarea::factory()->hijaDe($tareaPadre)->create([
            "responsable_id" => $responsableHija->id,
        ]);

        $archivo = UploadedFile::fake()->create("resultado.pdf", 100, "application/pdf");
        $this->actingAs($responsableHija)->post("/tareas/{$tareaHija->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertRedirect()->assertSessionHas("success");

        $response = $this->actingAs($responsablePadre)->get("/tareas/{$tareaPadre->id}")->assertOk();

        $response->assertInertia(fn ($page) => $page
            ->has("adjuntosDeTareasHijas", 1)
            ->where("adjuntosDeTareasHijas.0.nombre_original", "resultado.pdf")
            ->where("adjuntosDeTareasHijas.0.tarea_id", $tareaHija->id)
            ->where("adjuntosDeTareasHijas.0.tarea_hija_titulo", $tareaHija->titulo));
    }

    public function test_los_necesarios_de_una_tarea_hija_no_aparecen_en_la_tarea_padre(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsablePadre = $this->usuario(NivelJerarquico::Asistente, $obra);
        $responsableHija = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tareaPadre = Tarea::factory()->create([
            "responsable_id" => $responsablePadre->id,
            "unidad_organizacional_id" => $obra->id,
        ]);
        $tareaHija = Tarea::factory()->hijaDe($tareaPadre)->create([
            "responsable_id" => $responsableHija->id,
        ]);

        $archivo = UploadedFile::fake()->create("plano.pdf", 100, "application/pdf");
        $this->actingAs($responsableHija)->post("/tareas/{$tareaHija->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "necesario",
        ]);

        $response = $this->actingAs($responsablePadre)->get("/tareas/{$tareaPadre->id}")->assertOk();

        $response->assertInertia(fn ($page) => $page->has("adjuntosDeTareasHijas", 0));
    }

    public function test_no_se_pueden_adjuntar_archivos_a_una_tarea_completada(): void
    {
        $obra = UnidadOrganizacional::factory()->create();
        $responsable = $this->usuario(NivelJerarquico::Asistente, $obra);
        $tarea = Tarea::factory()->create([
            "responsable_id" => $responsable->id,
            "unidad_organizacional_id" => $obra->id,
            "estado" => EstadoTarea::Completada,
        ]);

        $archivo = UploadedFile::fake()->create("evidencia.pdf", 100, "application/pdf");

        $this->actingAs($responsable)->post("/tareas/{$tarea->id}/adjuntos", [
            "archivo" => $archivo,
            "categoria" => "evidencia",
        ])->assertSessionHas("error");

        $this->assertCount(0, $tarea->fresh()->adjuntos);
    }
}
