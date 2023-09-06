<?php

namespace DTApi\Helpers;

use Carbon\Carbon;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Models\Language;
use DTApi\Models\UserMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TeHelper
{
    public static function fetchLanguageFromJobId($id)
    {
        $language = Language::findOrFail($id);
        return $language1 = $language->language;
    }

    public static function getUserMeta($user_id, $key = false)
    {
        $user = UserMeta::where('user_id', $user_id)->first()->$key;
        if (!$key)
            return $user->usermeta()->get()->all();
        else {
            $meta = $user->usermeta()->where('key', '=', $key)->get()->first();
            return $meta ? $meta->value : '';
        }
    }

    public static function convertJobIdsInObjs($jobs_ids)
    {
        return Job::whereIn('id', $jobs_ids)->get();
    }

    public static function willExpireAt($due_time, $created_at)
    {
        $due_time = Carbon::parse($due_time);
        $created_at = Carbon::parse($created_at);

        // Calculate the difference in hours between the two times
        $difference = $due_time->diffInHours($created_at);

        // Define an array of conditions and their corresponding results
        $conditions = [
            ($difference <= 90) => $due_time,
            ($difference <= 24) => $created_at->addMinutes(90),
            ($difference <= 72) => $created_at->addHours(16),
        ];

        // Loop through the conditions and find the first condition that is true
        // When found, assign the corresponding result to the $time variable
        $time = null;
        foreach ($conditions as $condition => $result) {
            if ($condition) {
                $time = $result;
                break;
            }
        }

        return $time->format('Y-m-d H:i:s');

    }

}

