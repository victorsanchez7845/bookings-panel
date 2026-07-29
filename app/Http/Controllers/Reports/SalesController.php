<?php

namespace App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// REPOSITORY
use App\Repositories\Reports\SalesRepository;

// TRAIT
use App\Traits\RoleTrait;

class SalesController extends Controller
{
    use RoleTrait;

    private $SalesRepository;

    public function __construct(SalesRepository $SalesRepository)
    {
        $this->SalesRepository = $SalesRepository;
    }

    public function index(Request $request)
    {
        if (!$this->hasPermission(98)) {
            abort(403, 'NO TIENE AUTORIZACIÓN.');
        }

        // Obtiene el último segmento de la URL:
        // cancun, cabos o punta-cana
        $destination = last($request->segments());

        switch ($destination) {
            case 'cancun':
                $id = 1;
                break;

            case 'cabos':
                $id = 2;
                break;

            case 'punta-cana':
                $id = 3;
                break;

            default:
                abort(404, 'Destino no válido.');
        }

        return $this->SalesRepository->index($request, $id);
    }
}
