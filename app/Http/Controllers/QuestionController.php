<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Exam;
use App\Models\Question;


class QuestionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'exam_id' => 'required|exists:exams,id',
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,true_false,short_answer',
            'image' => 'nullable|image|max:2048',
            'options' => 'required|array',
            'correct_option' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $type = $request->input('type');
                    if ($type === 'mcq' && !in_array($value, ['a', 'b', 'c', 'd', 'e'])) {
                        $fail("The selected correct option for MCQ must be one of A, B, C, D, or E. Received: '$value'");
                    } elseif ($type === 'true_false' && !in_array($value, ['true', 'false'])) {
                        $fail("The selected correct option for True/False must be True or False. Received: '$value'");
                    } elseif ($type === 'short_answer' && $value !== 'answer') {
                        $fail("The selected correct option for Short Answer must be 'answer'. Received: '$value'");
                    }
                },
            ],
        ];
    
        if ($request->type === 'mcq') {
            $rules['options.*.option_text'] = 'nullable|string';
        } elseif ($request->type === 'true_false') {
            $rules['options.*.option_text'] = 'nullable|string';
        } elseif ($request->type === 'short_answer') {
            $rules['options.answer.option_text'] = 'required|string';
        }
    
        $validated = $request->validate($rules);
    
        // Handle image upload
        $imagePath = "";
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('question_images', 'public');
        }

    
        // Create question with image path
        $question = Question::create([
            'exam_id' => $validated['exam_id'],
            'question_text' => $validated['question_text'],
            'type' => $validated['type'],
            'image' => $imagePath, 
        ]);
    
        if ($validated['type'] === 'mcq') {
            $optionLabels = ['a', 'b', 'c', 'd', 'e'];
            $filledOptions = 0;
            foreach ($optionLabels as $label) {
                if (isset($validated['options'][$label]['option_text']) && !empty($validated['options'][$label]['option_text'])) {
                    $question->options()->create([
                        'option_text' => $validated['options'][$label]['option_text'],
                        'is_correct' => $validated['correct_option'] === $label,
                    ]);
                    $filledOptions++;
                }
            }
            if ($filledOptions < 2) {
                if ($imagePath) Storage::disk('public')->delete($imagePath);
                $question->delete();
                return back()->withErrors(['options' => 'At least 2 options must be filled for MCQ']);
            }
        } elseif ($validated['type'] === 'true_false') {
            $question->options()->create([
                'option_text' => 'True',
                'is_correct' => $validated['correct_option'] === 'true',
            ]);
            $question->options()->create([
                'option_text' => 'False',
                'is_correct' => $validated['correct_option'] === 'false',
            ]);
        } elseif ($validated['type'] === 'short_answer') {
            $question->options()->create([
                'option_text' => $validated['options']['answer']['option_text'],
                'is_correct' => true,
            ]);
        }
    
        return redirect()->route('questions.show', $request->exam_id)->with('success', 'Question added successfully');
    }
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Ensure we get a single Exam instance with its questions and options
        $exam = Exam::with('questions.options')->findOrFail($id);
        return view('question.index', compact('exam'));
    }



    public function showDetails(Question $question)
    {
        return response()->json([
            'exam_id' => $question->exam_id,
            'question_text' => $question->question_text,
            'type' => $question->type,
            'image' => $question->image,
            'options' => $question->options->map(function($option) {
                return [
                    'option_text' => $option->option_text,
                    'is_correct' => $option->is_correct
                ];
            })
        ]);
    }

  public function edit(Question $question)
    {
        // No need to find the question again - Laravel's route model binding already did it
    $question->load('options'); // Eager load the options relationship
    
    return response()->json([
        'exam_id' => $question->exam_id,
        'question_text' => $question->question_text,
        'type' => $question->type,
        'image' => $question->image,
        'options' => $question->options->map(function($option) {
            return [
                'option_text' => $option->option_text,
                'is_correct' => $option->is_correct
            ];
        })
    ]);
    }

    public function update(Request $request, Question $question)
    {
        // Validate request
        $rules = [
            'question_text' => 'required|string',
            'type' => 'required|in:mcq,true_false,short_answer',
            'correct_option' => 'required',
            'exam_id' => 'required|exists:exams,id'
        ];
    
        if ($request->type === 'mcq') {
            $rules['options'] = 'required|array|min:2';
        } elseif ($request->type === 'short_answer') {
            $rules['options.answer.option_text'] = 'required|string';
        }
    
        $validated = $request->validate($rules);
    
        // Update question
        $question->update([
            'question_text' => $validated['question_text'],
            'type' => $validated['type'],
            'exam_id' => $validated['exam_id']
        ]);
    
        // Handle image upload
        if ($request->hasFile('image')) {
            $question->update([
                'image' => $request->file('image')->store('question_images', 'public')
            ]);
        } elseif ($request->has('remove_image')) {
            Storage::disk('public')->delete($question->image);
            $question->update(['image' => null]);
        }
    
        // Update options
        $question->options()->delete();
    
        if ($request->type === 'mcq') {
            foreach ($request->options as $key => $option) {
                if (!empty($option['option_text'])) {
                    $question->options()->create([
                        'option_text' => $option['option_text'],
                        'is_correct' => $request->correct_option === $key
                    ]);
                }
            }
        } 
        elseif ($request->type === 'true_false') {
            $question->options()->createMany([
                ['option_text' => 'True', 'is_correct' => $request->correct_option === 'true'],
                ['option_text' => 'False', 'is_correct' => $request->correct_option === 'false']
            ]);
        }
        elseif ($request->type === 'short_answer') {
            $question->options()->create([
                'option_text' => $request->input('options.answer.option_text'),
                'is_correct' => true
            ]);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Question updated successfully'
        ]);
    }

    public function destroy(Question $question)
    {
        $question->options()->delete();
        $question->delete();
        return response()->json(['success' => true]);
    }
}
