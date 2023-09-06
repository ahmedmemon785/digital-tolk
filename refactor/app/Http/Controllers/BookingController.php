<?php

namespace DTApi\Http\Controllers;

use App\Http\Requests\CreateBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use DTApi\Models\Distance;
use DTApi\Models\Job;
use DTApi\Repository\BookingRepository;
use Illuminate\Http\Request;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * Get a list of jobs for a user or all jobs for admin/superadmin.
     *
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        //Its not a good approach to assign values in if and also we can use direct values if variable is not used anywhere else
        if (!empty($request->get('user_id'))) {
            return response($this->repository->getUsersJobs($request->get('user_id')));
            //Here ADMIN_ROLE_ID and SUPERADMIN_ROLE_ID should be constants called from User model and there value should be set in .env file
        }
        if (in_array($request->__authenticatedUser->user_type, [env('ADMIN_ROLE_ID'), env('SUPERADMIN_ROLE_ID')], true)) {
            return response($this->repository->getAll($request));
        }
        //I've removed the use of variables because it was immediately used once and it is better to use direct values
        return null;
    }

    /**
     * Get a specific job by ID.
     *
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        return response($this->repository->with('translatorJobRel.user')->find($id));
    }

    /**
     * Store a new job.
     *
     * @param Request $request
     * @return mixed
     */
    public function store(CreateBookingRequest $request)
    {
        //Validation should be done in Request class or controller as controllers are for handling validation and response

        $data = $request->validated();

        $response = $this->repository->store($request->__authenticatedUser, $data);

        return response($response);
    }

    /**
     * Update a job.
     *
     * @param $id
     * @param Request $request
     * @return mixed
     */
    //We can bind the model directly here instead of passing id and then finding the model
    public function update(Job $job, UpdateBookingRequest $request)
    {
        $response = $this->repository->updateJob($job, $request->validated(), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * Store an immediate job email.
     *
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {
        $response = $this->repository->storeJobEmail($request->all());

        return response($response);
    }

    /**
     * Get job history for a user.
     *
     * @param Request $request
     * @return mixed|null
     */
    public function getHistory(Request $request)
    {
        if (!empty($request->get('user_id'))) {
            return response($this->repository->getUsersJobsHistory($request->get('user_id'), $request));
        }

        return null;
    }

    /**
     * Accept a job request.
     *
     * @param Request $request
     * @param bool $withId
     * @return mixed
     */
    //This method can be used for both accepting a job with id and without id
    public function acceptJob(Request $request, $withId = false)
    {
        if ($withId) {
            $response = $this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser);
        } else {
            $response = $this->repository->acceptJob($request->all(), $request->__authenticatedUser);
        }
        return response($response);
    }

    /**
     * Accept a job with a specific ID.
     *
     * @param Request $request
     * @return mixed
     */
    public function acceptJobWithId(Request $request)
    {
        $response = $this->repository->acceptJobWithId($request->get('job_id'), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * Cancel a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {
        $response = $this->repository->cancelJobAjax($request->all(), $request->__authenticatedUser);

        return response($response);
    }

    /**
     * End a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {
        return response($this->repository->endJob($request->all()));
    }

    /**
     * Handle a customer no-show situation.
     *
     * @param Request $request
     * @return mixed
     */
    public function customerNotCall(Request $request)
    {
        $response = $this->repository->customerNotCall($request->all());

        return response($response);
    }

    /**
     * Get potential jobs for a user.
     *
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {
        $response = $this->repository->getPotentialJobs($request->__authenticatedUser);

        return response($response);
    }

    /**
     * Update job distance and other details.
     *
     * @param Request $request
     * @return mixed
     */
    public function distanceFeed(Request $request)
    {
        //This function also needs to be rewritten with separation of request tasks in request class
        $data = $request->all();
//        $data = $request->validated(); After adding request class we can use this line instead
        //Same work below can be done with
        $distance = $data['distance'] ?? "";
        $time = $data['time'] ?? "";
        $jobId = $data['jobid'] ?? "";
        $session = $data['session_time'] ?? "";


        if ($data['flagged'] == 'true') {
            // Refactor Comment: Added a validation check and message for 'admincomment'.
            if ($data['admincomment'] == '') {
                return "Please, add comment";
            }
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        $manuallyHandled = ($data['manually_handled'] === 'true') ? 'yes' : 'no';
        $byAdmin = ($data['by_admin'] === 'true') ? 'yes' : 'no';
        $adminComment = $data['admincomment'] ?? "";

        //We could have
        // $job = Job::find($jobid);
        if ($time || $distance) {
            // There could be a job model function updateDistance() which can be called here
            //$job->updateDistance($distance, $time);
            $affectedRows = Distance::where('job_id', '=', $jobId)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($adminComment || $session || $flagged || $manuallyHandled || $byAdmin) {

            // There could be a job model function updateAdminComments() which can be called here
            // $job->updateAdminComments($admincomment, $session, $flagged, $manually_handled, $by_admin);
            $affectedRows1 = Job::where('id', '=', $jobId)->update(array('admin_comments' => $adminComment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manuallyHandled, 'by_admin' => $byAdmin));

        }
        return response('Record updated!');
    }

    /**
     * Reopen a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function reopen(Request $request)
    {
        $response = $this->repository->reopen($request->all());

        return response($response);
    }

    /**
     * Resend notifications for a job.
     *
     * @param Request $request
     * @return mixed
     */
    public function resendNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);
        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Resend SMS notifications for a job.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            //Here response should be from base controller with necessary status code and message
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}

