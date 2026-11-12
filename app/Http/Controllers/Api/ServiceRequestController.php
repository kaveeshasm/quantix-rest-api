<?php

namespace App\Http\Controllers\Api;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignRequest;
use App\Http\Requests\ChangeStatusRequest;
use App\Http\Requests\DecisionRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateServiceRequestRequest;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\RequestWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ServiceRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        Gate::authorize('viewAny', ServiceRequest::class);

        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::enum(RequestStatus::class)],
            'sort' => ['nullable', Rule::in(['created_at', 'updated_at', 'title', 'status'])],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $requests = ServiceRequest::query()
            ->visibleTo($request->user())
            ->with(['requester', 'assignee'])
            ->when($filters['search'] ?? null, fn ($q, $term) => $q->where(
                fn ($q) => $q->where('title', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->orderBy($filters['sort'] ?? 'created_at', $filters['direction'] ?? 'desc')
            ->paginate($filters['per_page'] ?? 15)
            ->withQueryString();

        return ServiceRequestResource::collection($requests);
    }

    public function store(StoreServiceRequestRequest $request, RequestWorkflow $workflow): JsonResponse
    {
        $user = $request->user();

        $serviceRequest = DB::transaction(function () use ($request, $user) {
            $serviceRequest = ServiceRequest::create([
                'requester_id' => $user->id,
                'title' => $request->validated('title'),
                'description' => $request->validated('description'),
                'category' => $request->validated('category'),
                'status' => RequestStatus::Draft,
            ]);

            $serviceRequest->activityLogs()->create([
                'user_id' => $user->id,
                'action' => 'created',
                'to_status' => RequestStatus::Draft,
            ]);

            return $serviceRequest;
        });

        if ($request->boolean('submit')) {
            $workflow->transition($serviceRequest, RequestStatus::Submitted, $user);
        }

        return (new ServiceRequestResource($serviceRequest->load(['requester', 'assignee'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        Gate::authorize('view', $serviceRequest);

        $serviceRequest->load([
            'requester',
            'assignee',
            'comments' => fn ($q) => $q->oldest(),
            'comments.user',
            'activityLogs' => fn ($q) => $q->oldest(),
            'activityLogs.user',
        ]);

        return new ServiceRequestResource($serviceRequest);
    }

    public function update(UpdateServiceRequestRequest $request, ServiceRequest $serviceRequest): ServiceRequestResource
    {
        if (! $serviceRequest->status->isEditable()) {
            abort(422, "A request in '{$serviceRequest->status->value}' status cannot be edited.");
        }

        DB::transaction(function () use ($request, $serviceRequest) {
            $serviceRequest->update($request->validated());

            $serviceRequest->activityLogs()->create([
                'user_id' => $request->user()->id,
                'action' => 'updated',
                'from_status' => $serviceRequest->status,
                'to_status' => $serviceRequest->status,
            ]);
        });

        return new ServiceRequestResource($serviceRequest->load(['requester', 'assignee']));
    }

    public function submit(Request $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow): ServiceRequestResource
    {
        Gate::authorize('submit', $serviceRequest);

        $data = $request->validate(['comment' => ['nullable', 'string', 'max:1000']]);

        $workflow->transition($serviceRequest, RequestStatus::Submitted, $request->user(), $data['comment'] ?? null);

        return new ServiceRequestResource($serviceRequest->load(['requester', 'assignee']));
    }

    public function decision(DecisionRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow): ServiceRequestResource
    {
        $to = match ($request->validated('decision')) {
            'approve' => RequestStatus::Approved,
            'reject' => RequestStatus::Rejected,
            'return' => RequestStatus::Returned,
        };

        $workflow->transition($serviceRequest, $to, $request->user(), $request->validated('comment'));

        return new ServiceRequestResource($serviceRequest->load(['requester', 'assignee']));
    }

    public function assign(AssignRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow): ServiceRequestResource
    {
        $workflow->transition(
            $serviceRequest,
            RequestStatus::Assigned,
            $request->user(),
            $request->validated('comment'),
            ['assignee_id' => $request->validated('assignee_id')],
        );

        return new ServiceRequestResource($serviceRequest->load(['requester', 'assignee']));
    }

    public function status(ChangeStatusRequest $request, ServiceRequest $serviceRequest, RequestWorkflow $workflow): ServiceRequestResource
    {
        $workflow->transition(
            $serviceRequest,
            RequestStatus::from($request->validated('status')),
            $request->user(),
            $request->validated('comment'),
        );

        return new ServiceRequestResource($serviceRequest->load(['requester', 'assignee']));
    }
}
