<?php

declare(strict_types=1);

namespace CodelyTv\DivergentChange\Controller;

use CodelyTv\DivergentChange\Platform;

final class CourseStepsGetController
{
    const VIDEO_DURATION_PAUSES_MULTIPLIER  = 1.1;
    const QUIZ_TIME_PER_QUESTION_MULTIPLIER = 0.5;
    const STEP_TYPE_VIDEO                   = 'video';
    const STEP_TYPE_QUIZ                              = 'quiz';
    private Platform $platform;

    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    public function get(string $courseId): string
    {
        $csv = $this->platform->findCourseSteps($courseId);

        $results = '[';

        $csvLines = explode(PHP_EOL, $csv);

        foreach ($csvLines as $index => $row) {
            $row = str_getcsv($row);

            if (empty($csv)) {
                continue;
            }

            [$stepId, $type, $quizTotalQuestions, $videoDuration] = $row;

            $stepDurationInMinutes = 0;
            $points                = 0;

            if ($type === self::STEP_TYPE_VIDEO) {
                $stepDurationInMinutes = $videoDuration * self::VIDEO_DURATION_PAUSES_MULTIPLIER;
            }

            if ($type === self::STEP_TYPE_QUIZ) {
                $stepDurationInMinutes = $quizTotalQuestions * self::QUIZ_TIME_PER_QUESTION_MULTIPLIER;
            }

            if ($type !== self::STEP_TYPE_VIDEO && $type !== self::STEP_TYPE_QUIZ) {
                continue;
            }

            if ($type === self::STEP_TYPE_VIDEO) {
                $points = $stepDurationInMinutes * 100;
            }

            if ($type === self::STEP_TYPE_QUIZ) {
                $points = $quizTotalQuestions * self::QUIZ_TIME_PER_QUESTION_MULTIPLIER * 10;
            }

            $results .= json_encode(
                [
                    'id'       => $stepId,
                    'type'     => $type,
                    'duration' => $stepDurationInMinutes,
                    'points'   => $points,
                ],
                JSON_THROW_ON_ERROR
            );

            $hasMoreRows = $index !== count($csvLines) - 1;
            if ($hasMoreRows) {
                $results .= ',';
            }
        }

        $results .= ']';

        return $results;
    }
}
