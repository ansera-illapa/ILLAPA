<?php

namespace App\Http\Controllers\Apis;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\empresas;
use App\socios;
use App\clientes;
use App\User;
use App\sectoristas;
use App\sectores;
use App\gestores;

class UsuarioController extends Controller
{
    public function validarCorreo(Request $request)
    {
        $email = $request->email;

        $user = User::where('email', '=', $email)
                            ->first();
        if($user){

            $persona = User::select("users.email as userEmail", 
                                            "p.imagen as personaImagen", 
                                            "p.nombre as personaNombre", 
                                            "tdi.nombre as personaTipoIdentificacion",
                                            "p.numeroidentificacion as personaNumeroIdentificacion")
                                ->join('personas as p', 'p.id', '=', 'users.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('users.id', '=', $user->id)
                                ->first();

            $personaEmpresaDefault = sectoristas::where('socio_id', '=', 1)
                                            ->where('correo_id', '=', $user->id)
                                            ->where('estado', '=', 1)
                                            ->first();
            if($personaEmpresaDefault){
                return json_encode(array("code" => 1,  "resultUser"=>$persona, "pertenece"=>false, "idSectoristaValidado"=>$personaEmpresaDefault->id ,"loadValidar"=>true));
            }else{
                return json_encode(array("code" => 1,  "resultUser"=>$persona, "pertenece"=>true ,"loadValidar"=>true));
            }

        }else{
            return json_encode(array("code" => 0,  "resultUser"=>$user, "loadValidar"=>true));
        }

    }

    public function agregarEmpresa(Request $request)
    {
        $idSectorista = $request->idSectoristaValido;
        $nombreEmpresa = $request->nombreEmpresa;

        $sectorista = sectoristas::find($idSectorista);
        $sectorista->estado = 0;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $sectorista->id )->first();
        $sector->estado = 0;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)->first();
        $gestor->estado = 0;
        $gestor->update();

        $empresa = new empresas;
        $empresa->correo_id = $sectorista->correo_id;
        $empresa->nombre = $nombreEmpresa;
        $empresa->estado = 1;

        if($empresa->save()){
            return json_encode(true);
        }else{
            return json_encode(false);
        }

    }

    public function agregarSocio(Request $request)
    {
        $idSectorista = $request->idSectoristaValido;
        $idEmpresaSeleccionada = $request->idEmpresaSeleccionada;

        $sectorista = sectoristas::find($idSectorista);
        $sectorista->estado = 0;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $sectorista->id )->first();
        $sector->estado = 0;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)->first();
        $gestor->estado = 0;
        $gestor->update();

        $socio = new socios;
        $socio->empresa_id = $idEmpresaSeleccionada;
        $socio->correo_id = $sectorista->correo_id;
        $socio->estado = 1;

        if($socio->save()){
            return json_encode(true);
        }else{
            return json_encode(false);
        }

    }

    public function agregarSector(Request $request)
    {
        $idSocio = $request->idSocio;
        $descripcion = $request->descripcion;

        $sector = new sectores;
        $sector->socio_id = $idSocio;
        $sector->sectorista_id = null;
        $sector->descripcion = $descripcion;
        $sector->estado = 1;
        $sector->estSectorista = 0;
        $sector->estGestor = 0;

        if($sector->save()){
            return json_encode(true);
        }else{
            return json_encode(false);
        }
    }

    public function agregarGestor(Request $request)
    {
        $idSector = $request->idSector;
        $idSectorista = $request->idSectorista;


        $sectorista = sectoristas::find($idSectorista);
        $sectorista->estado = 0;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $idSectorista )->first();
        $sector->estado = 0;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)->first();
        $gestor->estado = 0;
        $gestor->update();

        $sectorUpdate = sectores::where('id', '=', $idSector)->first();
        $sectorUpdate->estGestor = 1;
        $sectorUpdate->update();

        $gestorExis = gestores::join('sectores as sct', 'sct.id', '=', 'gestores.sector_id')
                                ->where('gestores.correo_id', $sectorista->correo_id)
                                ->where('gestores.estado', 0)
                                ->where('sct.estado', '=', 1)
                                ->where('sct.socio_id', '!=', 1)
                                ->first();
        if($gestorExis){
            $gestor             = gestores::find($gestorExis->id);
            $gestor->sector_id  = $idSector;
            $gestor->estado     = 1;
        
            if($gestor->update()){
                return json_encode(true);
            }else{
                return json_encode(false);
            }

        }else{
            $gestor = new gestores;
            $gestor->sector_id = $idSector;
            $gestor->correo_id = $sectorista->correo_id;
            $gestor->estado = 1;
            if($gestor->save()){
                return json_encode(true);
            }else{
                return json_encode(false);
            }
        }
    }

    public function agregarSectorista(Request $request)
    {
        $idSocio = $request->idSocio;
        $idsSectores = $request->idsSectores;
        $idSectorista = $request->idSectorista;

        $sectorista = sectoristas::find($idSectorista);
        $sectorista->estado = 0;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $idSectorista )->first();
        $sector->estado = 0;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)->first();
        $gestor->estado = 0;
        $gestor->update();


        $sectoristaExis = sectoristas::where('correo_id', $sectorista->correo_id)
                                        ->where('estado', 0)
                                        ->where('socio_id', $idSocio)
                                        ->first();
        
        if($sectoristaExis){
            $sectoristaNuevo = sectoristas::find($sectoristaExis->id);
            $sectoristaNuevo->estado = 1;

            if($sectoristaNuevo->update()){
                
                $sectores = explode("-", $idsSectores);
                $longSectores = sizeof($sectores);
                for($x = 1; $x <= $longSectores; $x++){
                    
                    $sectorUpdate = sectores::where('id', '=', $sectores[$x])->first();
                    $sectorUpdate->estSectorista = 1;
                    $sectorUpdate->sectorista_id = $sectoristaNuevo->id;
                    $sectorUpdate->update();
                }

                return json_encode(true);
            }else{
                return json_encode(false);
            }
        }else{
            $sectoristaNuevo = new sectoristas;
            $sectoristaNuevo->socio_id = $idSocio;
            $sectoristaNuevo->correo_id = $sectorista->correo_id;
            $sectoristaNuevo->estado = 1;

            if($sectoristaNuevo->save()){
                
                $sectores = explode("-", $idsSectores);
                $longSectores = sizeof($sectores);
                for($x = 1; $x <= $longSectores; $x++){
                    
                    $sectorUpdate = sectores::where('id', '=', $sectores[$x])->first();
                    $sectorUpdate->estSectorista = 1;
                    $sectorUpdate->sectorista_id = $sectoristaNuevo->id;
                    $sectorUpdate->update();
                }

                return json_encode(true);
            }else{
                return json_encode(false);
            }
        }
        

    }




    public function validarCorreoSocio(Request $request)
    {
        $email = $request->email;
        $idEmpresaSeleccionada = $request->idEmpresaSeleccionada;

        $user = User::where('email', '=', $email)
                            ->where('estado','=',1)
                            ->first();
                            
        if($user){

            $persona = User::select("users.email as userEmail", 
                                            "p.imagen as personaImagen", 
                                            "p.nombre as personaNombre", 
                                            "tdi.nombre as personaTipoIdentificacion",
                                            "p.numeroidentificacion as personaNumeroIdentificacion")
                                ->join('personas as p', 'p.id', '=', 'users.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('users.id', '=', $user->id)
                                ->first();

            $personaEmpresaDefault = sectoristas::where('socio_id', '=', 1)
                                            ->where('correo_id', '=', $user->id)
                                            ->where('estado', '=', 1)
                                            ->first();
            if($personaEmpresaDefault){
            
                return json_encode(array("code" => 1,  "resultUser"=>$persona, "pertenece"=>false, "idSectoristaValidado"=>$personaEmpresaDefault->id ,"loadValidar"=>true));
            
            }else{
                $socio = socios::where('empresa_id', '=', $idEmpresaSeleccionada)
                                ->where('correo_id', '=',  $user->id)
                                ->first();
                if($socio){
                    return json_encode(array("code" => 1,  "resultUser"=>$persona, "pertenece"=>true, "siAsociado"=>true , "loadValidar"=>true));
                    
                }else{
                    return json_encode(array("code" => 1,  "resultUser"=>$persona, "pertenece"=>true, "siAsociado"=>false , "loadValidar"=>true));
                    
                }
            }

        }else{
            return json_encode(array("code" => 0,  "resultUser"=>$user, "loadValidar"=>true));
        }

    }


    public function mostrarEmpresas()
    {

        $empresas = empresas::select("empresas.nombre as empresaNombre", "u.email as userEmail", 
                                        "empresas.id as empresaId",
                                        "tdi.nombre as personaTipoIdentificacion",
                                        "p.numeroidentificacion as personaNumeroIdentificacion",
                                        "p.imagen as personaImagen")
                            ->join('users as u', 'u.id', '=', 'empresas.correo_id')
                            ->join('personas as p', 'p.id', '=', 'u.persona_id')
                            ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                            ->where('empresas.estado', '=', 1)
                            ->get();

        if (sizeof($empresas) > 0){
            return json_encode(array("code" => true, "result"=>$empresas , "load"=>true));
        }else{
            return json_encode(array("code" => false, "load"=>true));
        }

    }

    public function mostrarSocios($empresaid)
    {
        
        $empresa = empresas::select("empresas.nombre as empresaNombre", "u.email as userEmail", 
                                            "empresas.id as empresaId",
                                            "tdi.nombre as personaTipoIdentificacion",
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'empresas.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('empresas.id', '=', $empresaid)
                                ->first();

        $sociosEmpresa = socios::select("socios.id as socioId", "socios.empresa_id as empresaId", 
                                        "u.email as userEmail", 
                                        "tdi.nombre as personaTipoIdentificacion",
                                        "p.numeroidentificacion as personaNumeroIdentificacion",
                                        "socios.estado as socioEstado", "p.nombre as personaNombre", 
                                        "p.imagen as personaImagen")
                            ->join('users as u', 'u.id', '=', 'socios.correo_id')
                            ->join('personas as p', 'p.id', '=', 'u.persona_id')
                            ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                            ->where('socios.empresa_id', '=', $empresaid)
                            ->get();

                            // select s.id, s.empresa_id, s.estado, p.nombre, p.imagen
                            // from socios s, personas p, users u
                            // where s.correo_id = u.id && u.persona_id = p.id && empresa_id = ;

        if (sizeof($sociosEmpresa) > 0){
            return json_encode(array("code" => true, "result"=>$sociosEmpresa, "empresa"=>$empresa, "load"=>true  ));
        }else{
            return json_encode(array("code" => false, "message"=>"No hay empresas !", "empresa"=>$empresa, "load"=>true));
        }

    }


    public function mostrarUsuarios($socioId)
    {

        $socioEmpresa = socios::select("socios.id as socioId", "socios.empresa_id as empresaId",
                                        "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion",
                                        "p.numeroidentificacion as personaNumeroIdentificacion",
                                        "socios.estado as socioEstado", "p.nombre as personaNombre", 
                                        "p.imagen as personaImagen")
                            ->join('users as u', 'u.id', '=', 'socios.correo_id')
                            ->join('personas as p', 'p.id', '=', 'u.persona_id')
                            ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                            ->where('socios.id', '=', $socioId)
                            ->first();


        $sectoristasSocio = socios::select("socios.id as socioId",  'scts.id as sectoristasId',
                                        "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion",
                                        "p.numeroidentificacion as personaNumeroIdentificacion",
                                        "p.nombre as personaNombre", 
                                        "p.imagen as personaImagen")
                            ->join('sectoristas as scts', 'scts.socio_id', '=', 'socios.id')
                            ->join('users as u', 'u.id', '=', 'scts.correo_id')
                            ->join('personas as p', 'p.id', '=', 'u.persona_id')
                            ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                            ->where('scts.estado', '=', 1)
                            ->where('socios.id', '=', $socioId)
                            ->get();

                                //select s.id as socioId, scts.id as sectoristaId
                                // from socios s, sectoristas scts
                                // where s.id = scts.socio_id;


        $gestoresSocio = socios::select("socios.id as socioId",  'g.id as gestorId',
                                            "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion",
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "p.nombre as personaNombre", 
                                            "p.imagen as personaImagen")

                                // ->join('sectoristas as scts', 'scts.socio_id', '=', 'socios.id')
                                ->join('sectores as sct', 'sct.socio_id', '=', 'socios.id')
                                ->join('gestores as g', 'g.sector_id', '=', 'sct.id')
                                ->join('users as u', 'u.id', '=', 'g.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('g.estado', '=', 1)
                                ->where('socios.id', '=', $socioId)
                                ->get();

            // select s.id as socioId, scts.id as sectoristasId, g.id as gestorId, sct.id as sectorId
            // from socios s, sectoristas scts, gestores g, sectores sct
            // where scts.socio_id = s.id && g.sector_id = sct.id && sct.sectorista_id = scts.id && s.id =  ;



        if (sizeof($sectoristasSocio) > 0 || sizeof($gestoresSocio) > 0 ){
            return json_encode(array("code" => true, "resultSectorista"=>$sectoristasSocio, "resultGestores"=>$gestoresSocio ,"socio"=>$socioEmpresa, "load"=>true  ));
        }else{
            return json_encode(array("code" => false, "socio"=>$socioEmpresa, "load"=>true));
        }

    }

    public function mostrarSectorGestor($gestorId, $socioid)
    {

        // $gestor = gestores::find($gestorId);
        $gestorSector = gestores::select("sct.descripcion as sectoresDescripcion", "sct.id as sectorId")
                            ->join('sectores as sct', 'sct.id', '=', 'gestores.sector_id')
                            ->where('sct.estado', '=', 1)
                            ->where('gestores.id', '=', $gestorId)
                            ->get();

        $sectoresSocio = sectores::select("sectores.descripcion as sectoresDescripcion",
                                            "sectores.id as id",
                                            "sectores.estGestor as estadoSectoristaGestor")
                            ->where('sectores.socio_id', '=', $socioid)
                            ->get();

        if (sizeof($gestorSector) > 0 ){
            return json_encode(array("code" => true, "sectores"=>$sectoresSocio, "sectoresSeleccionados"=>$gestorSector ,"load"=>true  ));
        }else{
            return json_encode(array("code" => false,  "load"=>true));
        }

    }

    public function mostrarSectoresSocio($socioid)
    {

        $sectoresSocio = sectores::select("sectores.descripcion as sectoresDescripcion",
                                            "sectores.id as id",
                                            "sectores.estGestor as estadoGestor",
                                            "sectores.estSectorista as estadoSectorista")
                                ->where('sectores.socio_id', '=', $socioid)
                                ->get();

        if (sizeof($sectoresSocio) > 0 ){
            return json_encode(array("code" => true, 
                                    "sectores"=>$sectoresSocio, 
                                    "load"=>true  ));
        }else{
            return json_encode(array("code" => false,  
                                    "load"=>true));
        }

    }

    public function mostrarSectoresSectorista($sectoristaId, $socioid)
    {

        $sectoristaSectores = sectores::select("descripcion as sectoresDescripcion", "id as sectorId")
                                    ->where('estado', '=', 1)
                                    ->where('sectorista_id', '=', $sectoristaId)
                                    ->get();

        $sectoresSocio = sectores::select("sectores.descripcion as sectoresDescripcion",
                                            "sectores.id as id",
                                            "sectores.estSectorista as estadoSectoristaGestor")
                                    ->where('sectores.socio_id', '=', $socioid)
                                    ->get();

        if (sizeof($sectoristaSectores) > 0 ){
            return json_encode(array("code" => true, "sectores"=>$sectoresSocio, "sectoresSeleccionados"=>$sectoristaSectores ,"load"=>true ,"siSectores" => false ));
        }elseif(sizeof($sectoresSocio) > 0){
            return json_encode(array("code" => false,  "load"=>true, "sectores"=>$sectoresSocio, "siSectores" => true));
        }else{
            return json_encode(array("code" => false,  "load"=>true, "siSectores" => false));
        }

    }

    public function editarSectorGestor(Request $request)
    {
        $idGestor = $request->idUsuario;
        $idSectorAntiguo = $request->idsectorAntiguo;
        $idSectorNuevo = $request->idsectorNuevo;
        $idSocio= $request->idsocio;
        
        $sectorAntiguo = sectores::find($idSectorAntiguo);
        $sectorAntiguo->estGestor = 0;
        $sectorAntiguo->update();

        $gestor = gestores::find($idGestor);
        $gestor->sector_id = $idSectorNuevo;
        $gestor->update();

        $sectorNuevo = sectores::find($idSectorNuevo);
        $sectorNuevo->estGestor = 1;

        if( $sectorNuevo->update()){
            return json_encode(array("code" => true,  "load"=>true));
        }else{
            return json_encode(array("code" => false, "load"=>true));
        }
    }

    public function editarSectorSectorista(Request $request)
    {
        $idSectorista = $request->idUsuario;
        $idSectorAntiguo = $request->idsectorAntiguo;
        $idSectorNuevo = $request->idsectorNuevo;
        $idSocio= $request->idsocio;
        
        $sectorAntiguo = sectores::find($idSectorAntiguo);
        $sectorAntiguo->sectorista_id = null;
        $sectorAntiguo->estSectorista = 0;
        $sectorAntiguo->update();

        $sectorNuevo = sectores::find($idSectorNuevo);
        $sectorNuevo->sectorista_id = $idSectorista;
        $sectorNuevo->estSectorista = 1;

        if( $sectorNuevo->update()){
            return json_encode(array("code" => true,  "load"=>true));
        }else{
            return json_encode(array("code" => false, "load"=>true));
        }
    }

    public function anadirSectorSectorista(Request $request)
    {
        $idSectorista = $request->idUsuario;
        $idSectorNuevo = $request->idsectorNuevo;
        $idSocio= $request->idsocio;
        
        $sectorNuevo = sectores::find($idSectorNuevo);
        $sectorNuevo->sectorista_id = $idSectorista;
        $sectorNuevo->estSectorista = 1;

        if( $sectorNuevo->update()){
            return json_encode(array("code" => true,  "load"=>true));
        }else{
            return json_encode(array("code" => false, "load"=>true));
        }


    }

    public function revocarSectorSectorista(Request $request)
    {
        $idSectorista = $request->idUsuario;
        $idSectorAntiguo = $request->idsectorAntiguo;
        $idSocio= $request->idsocio;
        
        $sectorAntiguo = sectores::find($idSectorAntiguo);
        $sectorAntiguo->sectorista_id = null;
        $sectorAntiguo->estSectorista = 0;

        if( $sectorAntiguo->update()){
            return json_encode(array("code" => true,  "load"=>true));
        }else{
            return json_encode(array("code" => false, "load"=>true));
        }
    }

    public function editarSectorista(Request $request)
    {
        $idSectorista   = $request->idUsuario;
        $idSocio        = $request->idsocio;

        $sectoresAntiguos = sectores::where('sectorista_id', $idSectorista)->get();
        foreach($sectoresAntiguos as $sectorAntiguo){
            $sectoresAntiguo  = sectores::find($sectorAntiguo->id);
            $sectoresAntiguo->sectorista_id = null;
            $sectoresAntiguo->estSectorista = 0;
            $sectoresAntiguo->update();
        }
        

        $sectorista  = sectoristas::find($idSectorista);
        $sectoristaCorreo_id = $sectorista->correo_id;
        $sectorista->delete();

        $sectorSinGestor = sectores::where('estGestor', '=', 0)
                                    ->first();

        $sectorUpdate = sectores::where('id', '=', $sectorSinGestor->id)->first();
        $sectorUpdate->estGestor = 1;
        $sectorUpdate->update();

        $gestorExis = gestores::join('sectores as sct', 'sct.id', '=', 'gestores.sector_id')
                                ->where('gestores.correo_id', $sectoristaCorreo_id)
                                ->where('gestores.estado', 0)
                                ->where('sct.estado', '=', 1)
                                ->where('sct.socio_id', '!=', 1)
                                ->first();
        if($gestorExis){
            $gestor             = gestores::find($gestorExis->id);
            $gestor->sector_id  = $sectorSinGestor->id;
            $gestor->estado     = 1;
        
            if($gestor->update()){
                return json_encode(array("code" => true, 
                                    "load"=>true));
            }else{
                return json_encode(array("code" => false, 
                                    "load"=>true));
            }

        }else{
            $gestor = new gestores;
            $gestor->sector_id = $sectorSinGestor->id;
            $gestor->correo_id = $sectoristaCorreo_id;
            $gestor->estado = 1;
            if($gestor->save()){
                return json_encode(array("code" => true, 
                                    "load"=>true));
            }else{
                return json_encode(array("code" => false, 
                                    "load"=>true));
            }
        }
    }

    public function editarGestor(Request $request)
    {
        $idGestor   = $request->idUsuario;
        $idSocio    = $request->idsocio;

        $gestor          = gestores::find($idGestor);
        $gestorCorreo_id = $gestor->correo_id;
        $gestorSector_id = $gestor->sector_id;
        $gestor->delete();

        $sectorUpdate = sectores::where('id', '=', $gestorSector_id)->first();
        $sectorUpdate->estGestor = 0;
        $sectorUpdate->update();

        $sectoristaExis = sectoristas::where('correo_id', $gestorCorreo_id)
                                        ->where('estado', 0)
                                        ->where('socio_id', $idSocio)
                                        ->first();
        if($sectoristaExis){
            $sectorista = sectoristas::find($sectoristaExis->id);
            $sectorista->estado = 1;
            if($sectorista->update()){
                return json_encode(array("code" => true, 
                                    "load"=>true));
            }else{
                return json_encode(array("code" => false, 
                                    "load"=>true));
            }
            

        }else{
            $sectorista = new sectoristas;
            $sectorista->socio_id = $idSocio;
            $sectorista->correo_id = $gestorCorreo_id;
            $sectorista->estado = 1;
            if($sectorista->save()){
                return json_encode(array("code" => true, 
                                    "load"=>true));
            }else{
                return json_encode(array("code" => false, 
                                    "load"=>true));
            }
        }
    }

    public function eliminarSectorSocio(Request $request)
    {
        $idSector = $request->idSector;
        $idSocio= $request->idsocio;
        
        $sector = sectores::where('estSectorista', '=', 0)
                            ->where('estGestor', '=', 0)
                            ->where('id', '=', $idSector)
                            ->first();

        if($sector){
            $sector = sectores::find($idSector);
            $sector->delete();
            return json_encode(array("code" => true,
                                    "load"=>true));
        }else{
            return json_encode(array("code" => false, 
                                    "load"=>true));
        }
    }


    public function mostrarSocioEmpresaSectores($socioId)
    {
        $socioEmpresa = socios::select("socios.id as socioId", "socios.empresa_id as empresaId",
                                            "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "socios.estado as socioEstado", "p.nombre as personaNombre", 
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'socios.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('socios.id', '=', $socioId)
                                ->first();

        $empresa = empresas::select("empresas.nombre as empresaNombre", "u.email as userEmail", 
                                            "empresas.id as empresaId",
                                            "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'empresas.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('empresas.id', '=', $socioEmpresa->empresaId)
                                ->first();
        
        
        $sectores = sectores::select(   
                                    'sectores.id as id', 
                                    'scts.id as idSectorista',
                                    'g.id as idGestor',
                                    'sectores.descripcion',
                                    'sectores.estado',
                                    'sectores.estSectorista',
                                    'sectores.estGestor'
                                    )
                            ->leftjoin('sectoristas as scts', 'scts.id', '=', 'sectores.id')
                            ->leftjoin('gestores as g', 'g.sector_id', '=', 'sectores.id')
                            ->where('sectores.socio_id', '=', $socioId)
                            ->get();

        return json_encode(array("code" => true, 
                                    "socio"=>$socioEmpresa, 
                                    "empresa"=>$empresa ,
                                    "load"=>true, 
                                    "sectores"=>$sectores   ));
    }


    public function mostrarSocioEmpresaSectorGestor($socioId, $gestorId)
    {
        $socioEmpresa = socios::select("socios.id as socioId", "socios.empresa_id as empresaId",
                                            "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "socios.estado as socioEstado", "p.nombre as personaNombre", 
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'socios.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('socios.id', '=', $socioId)
                                ->first();

        $empresa = empresas::select("empresas.nombre as empresaNombre", "u.email as userEmail", 
                                            "empresas.id as empresaId",
                                            "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'empresas.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('empresas.id', '=', $socioEmpresa->empresaId)
                                ->first();
        
        
        $sectores = sectores::select(   
                                    'sectores.id as id', 
                                    'scts.id as idSectorista',
                                    'g.id as idGestor',
                                    'sectores.descripcion',
                                    'sectores.estado',
                                    'sectores.estSectorista',
                                    'sectores.estGestor'
                                    )
                            ->leftjoin('sectoristas as scts', 'scts.id', '=', 'sectores.id')
                            ->leftjoin('gestores as g', 'g.sector_id', '=', 'sectores.id')
                            ->where('sectores.socio_id', '=', $socioId)
                            ->get();

        $sectorGestor = sectores::join('gestores as g', 'g.sector_id', '=', 'sectores.id')
                                ->where('sectores.socio_id', $socioId)                    
                                ->where('sectores.estado', 1)
                                ->where('g.id', $gestorId)
                                ->first();

        return json_encode(array("code" => true, 
                                    "socio"=>$socioEmpresa, 
                                    "empresa"=>$empresa ,
                                    "load"=>true, 
                                    "sectores"=>$sectores,
                                    "sectorGestor" => $sectorGestor
                                ));
    }

    public function mostrarSocioEmpresaSectoresSectorista($socioId, $sectoristaId)
    {
        $socioEmpresa = socios::select("socios.id as socioId", "socios.empresa_id as empresaId",
                                            "u.email as userEmail", "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "socios.estado as socioEstado", "p.nombre as personaNombre", 
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'socios.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('socios.id', '=', $socioId)
                                ->first();

        $empresa = empresas::select("empresas.nombre as empresaNombre", "u.email as userEmail", 
                                            "empresas.id as empresaId",
                                            "tdi.nombre as personaTipoIdentificacion", 
                                            "p.numeroidentificacion as personaNumeroIdentificacion",
                                            "p.imagen as personaImagen")
                                ->join('users as u', 'u.id', '=', 'empresas.correo_id')
                                ->join('personas as p', 'p.id', '=', 'u.persona_id')
                                ->join('tiposDocumentosIdentidad as tdi', 'tdi.id', '=', 'p.tipoDocumentoIdentidad_id')
                                ->where('empresas.id', '=', $socioEmpresa->empresaId)
                                ->first();
        
        
        $sectores = sectores::select(   
                                    'sectores.id as id', 
                                    'scts.id as idSectorista',
                                    'g.id as idGestor',
                                    'sectores.descripcion',
                                    'sectores.estado',
                                    'sectores.estSectorista',
                                    'sectores.estGestor'
                                    )
                            ->leftjoin('sectoristas as scts', 'scts.id', '=', 'sectores.id')
                            ->leftjoin('gestores as g', 'g.sector_id', '=', 'sectores.id')
                            ->where('sectores.socio_id', '=', $socioId)
                            ->get();

        $sectoresSectorista = sectores::where('sectores.socio_id', $socioId)                    
                                        ->where('sectores.estado', 1)
                                        ->where('sectores.sectorista_id', $sectoristaId)
                                        ->first();

        return json_encode(array("code" => true, 
                                    "socio"=>$socioEmpresa, 
                                    "empresa"=>$empresa ,
                                    "load"=>true, 
                                    "sectores"=>$sectores,
                                    "sectoresSectorista" => $sectoresSectorista
                                ));
    }

    public function degradarGestor($idSocio, $idGestor)
    {

        $gestor = gestores::where('id', $idGestor)
                            ->first();
        
        $sectorista = sectoristas::where('correo_id', $gestor->correo_id)
                                    ->where('estado', 0)
                                    ->where('socio_id', 1)
                                    ->first();

        $sectorista = sectoristas::find($sectorista->id);
        $sectorista->estado = 1;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $sectorista->id)
                            ->first();
        $sector->estado = 1;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)
                            ->first();
        $gestor->estado = 1;
        $gestor->update();

        $gestor             = gestores::find($idGestor);
        $gestor->estado     = 0;

        $sectorUpdate = sectores::where('id', '=', $gestor->sector_id)
                                ->first();
        $sectorUpdate->estGestor = 0;
        $sectorUpdate->update();

        if($gestor->save()){
            return json_encode(true);
        }else{
            return json_encode(false);
        }
    }
    
    public function degradarSectorista($idSocio, $idSectorista)
    {
        $sectoristaSeleccionado = sectoristas::find($idSectorista);

        $sectorista = sectoristas::where('estado', 0)
                                ->where('socio_id', 1)
                                ->where('correo_id', $sectoristaSeleccionado->correo_id)
                                ->first();
                                
        $sectorista = sectoristas::find($sectorista->id);
        $sectorista->estado = 1;
        $sectorista->update();

        $sector = sectores::where('sectorista_id', '=', $sectorista->id )
                            ->first();
        $sector->estado = 1;
        $sector->update();

        $gestor = gestores::where('sector_id', '=', $sector->id)
                            ->first();
        $gestor->estado = 1;
        $gestor->update();

        $sectoristaSeleccionado->estado = 0;
        if($sectoristaSeleccionado->update()){
            
            $sectores = sectores::where('sectorista_id', $idSectorista)
                                ->get();

            foreach($sectores as $sector){
                $sectorSelect = sectores::find($sector->id);
                $sectorSelect->estSectorista = 0;
                $sectorSelect->sectorista_id = null;
                $sectorSelect->update();
            }

            return json_encode(true);
        }else{
            return json_encode(false);
        }
        
    }

}
