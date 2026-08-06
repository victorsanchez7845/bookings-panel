<?php

namespace App\Http\Controllers\Reservations;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// REPOSITORIES
use App\Repositories\Reservations\ReservationsRepository;
use App\Repositories\Reservations\DetailRepository;
use App\Repositories\Reservations\UploadRepository;

// TRAITS
use App\Traits\RoleTrait;

// MODELS
use App\Models\Reservation;
use App\Models\ReservationsItem;

// REQUESTS
use App\Http\Requests\ReservationDetailsRequest;
use App\Http\Requests\ReservationFollowUpsRequest;
use App\Http\Requests\ReservationItemRequest;
use App\Http\Requests\ReservationConfirmationRequest;

class ReservationsController extends Controller
{
    use RoleTrait;

    private $ReservationsRepository;
    private $DetailRepository;
    private $UploadRepository;

    public function __construct(
        ReservationsRepository $ReservationsRepository,
        DetailRepository $DetailRepository,
        UploadRepository $UploadRepository
    ) {
        $this->ReservationsRepository = $ReservationsRepository;
        $this->DetailRepository = $DetailRepository;
        $this->UploadRepository = $UploadRepository;
    }

    public function detail(Request $request, $id)
    {
        if ($this->hasPermission(61)) {
            return $this->DetailRepository->detail($request, $id);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function update(
        ReservationDetailsRequest $request,
        ReservationsRepository $reservationRepository,
        Reservation $reservation
    ) {
        if ($this->hasPermission(11)) {
            return $reservationRepository->update(
                $request,
                $reservation
            );
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function destroy(
        Request $request,
        ReservationsRepository $reservationRepository,
        Reservation $reservation
    ) {
        if ($this->hasPermission(24)) {
            return $reservationRepository->destroy(
                $request,
                $reservation
            );
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function deleteReservation(
        Request $request,
        ReservationsRepository $reservationRepository
    ) {
        if ($this->hasPermission(24)) {
            return $reservationRepository->deleteReservation($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function deleteReservations(
        Request $request,
        ReservationsRepository $reservationRepository
    ) {
        if ($this->hasPermission(24)) {
            return $reservationRepository->deleteReservations($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function followups(
        ReservationFollowUpsRequest $request,
        ReservationsRepository $reservationRepository
    ) {
        if ($this->hasPermission(23)) {
            return $reservationRepository->follow_ups($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function get_exchange(
        Request $request,
        ReservationsRepository $reservationRepository,
        Reservation $reservation
    ) {
        return $reservationRepository->get_exchange(
            $request,
            $reservation
        );
    }

    public function editreservitem(
        ReservationItemRequest $request,
        ReservationsRepository $reservationRepository,
        ReservationsItem $item
    ) {
        if ($this->hasPermission(13)) {
            return $reservationRepository->editreservitem(
                $request,
                $item
            );
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function editReservationItemComment(
        Request $request,
        ReservationsRepository $reservationRepository
    ) {
        if ($this->hasPermission(13)) {
            return $reservationRepository->editReservationItemComment(
                $request
            );
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function arrivalConfirmation(
        ReservationConfirmationRequest $request,
        ReservationsRepository $reservationRepository
    ) {
        return $reservationRepository->sendArrivalConfirmation(
            $request
        );
    }

    public function departureConfirmation(
        Request $request,
        ReservationsRepository $reservationRepository
    ) {
        return $reservationRepository->sendDepartureConfirmation(
            $request
        );
    }

    public function paymentRequest(
        Request $request,
        ReservationsRepository $reservationRepository
    ) {
        return $reservationRepository->sendPaymentRequest(
            $request
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Enviar cupón operativo al proveedor
    |--------------------------------------------------------------------------
    */

    public function sendProviderVoucher(
        Request $request,
        ReservationsRepository $reservationRepository,
        Reservation $reservation
    ) {
        if ($this->hasPermission(61)) {
            return $reservationRepository->sendProviderVoucher(
                $request,
                $reservation
            );
        }

        return response()->json([
            'success' => false,
            'message' => 'No tiene autorización para enviar el cupón al proveedor.',
        ], 403);
    }

    public function uploadMedia(
        Request $request,
        UploadRepository $uploadRepository
    ) {
        if ($this->hasPermission(64)) {
            return $uploadRepository->add($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function deleteMedia(
        Request $request,
        UploadRepository $uploadRepository
    ) {
        if ($this->hasPermission(66)) {
            return $uploadRepository->delete($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function getMedia(Request $request)
    {
        if ($this->hasPermission(65)) {
            return $this->DetailRepository->getMedia($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function reorderMedia(Request $request)
    {
        if ($this->hasPermission(66)) {
            return $this->DetailRepository->reorderMedia($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }

    public function paymentLink(Request $request)
    {
        if ($this->hasPermission(61)) {
            return $this->DetailRepository->paymentLink($request);
        }

        abort(403, 'NO TIENE AUTORIZACIÓN.');
    }
}
