<?php

namespace App\Services\OfflineCbt;

use App\Models\Exam;

/**
 * The one place CBT answers are marked — used by the online submit
 * (CBTController::submit) and by offline-sync imports, so an exam taken
 * offline is scored exactly like one taken online.
 */
class CbtMarker
{
    /**
     * @param  Exam   $exam     loaded with questions.options
     * @param  array  $answers  [['question_id' => int, 'answer' => ?string], ...]
     * @return array{score: float, total_marks: float, attempted: int, total_questions: int, rows: array}
     *         rows = answers to store: [['question_id', 'option_id', 'short_answer'], ...]
     */
    public static function mark(Exam $exam, array $answers): array
    {
        $totalMarks = 0.0;
        $score      = 0.0;
        $attempted  = 0;
        $rows       = [];
        $seen       = [];

        foreach ($answers as $submitted) {
            $qid = (int) ($submitted['question_id'] ?? 0);
            if (isset($seen[$qid])) continue;          // one answer per question
            $seen[$qid] = true;

            $question = $exam->questions->firstWhere('id', $qid);
            if (!$question) continue;

            $questionMarks = (float) ($question->marks ?? 1.0);
            $totalMarks   += $questionMarks;

            $studentAnswer = trim((string) ($submitted['answer'] ?? ''));
            if ($studentAnswer === '') continue;

            $attempted++;
            $optionId        = null;
            $shortAnswerText = null;

            if ($question->type === 'short_answer') {
                $shortAnswerText = mb_substr($studentAnswer, 0, 255);
                $correctOption   = $question->options->where('is_correct', true)->first();
                if ($correctOption) {
                    $a = strtolower($studentAnswer);
                    $b = strtolower(trim($correctOption->option_text));
                    if ($a === $b) {
                        $score += $questionMarks;
                    } else {
                        similar_text($a, $b, $percent);
                        if ($percent >= 80) $score += $questionMarks;
                    }
                }
            } else {
                $selected = $question->options->first(fn ($o) => trim($o->option_text) === $studentAnswer);
                if ($selected) {
                    $optionId = $selected->id;
                    if ($selected->is_correct) $score += $questionMarks;
                }
            }

            $rows[] = ['question_id' => $qid, 'option_id' => $optionId, 'short_answer' => $shortAnswerText];
        }

        return [
            'score'           => $score,
            'total_marks'     => $totalMarks,
            'attempted'       => $attempted,
            'total_questions' => $exam->questions->count(),
            'rows'            => $rows,
        ];
    }
}
