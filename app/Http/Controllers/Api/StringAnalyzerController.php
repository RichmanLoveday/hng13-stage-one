<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStringAnalyzer;
use App\Models\StringAnalyzer as ModelsStringAnalyzer;
use App\Services\StringAnalyzerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StringAnalyzerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, StringAnalyzerService $stringAnalyzerService)
    {
        try {
            $stringAnalyzerServiceResult = $stringAnalyzerService
                ->getStringsByFitlering($request->all());

            // dd($stringAnalyzerServiceResult);
            if ($stringAnalyzerServiceResult->status === 'error') {
                return response()->json([
                    "message" => $stringAnalyzerServiceResult->message,
                    "status" => $stringAnalyzerServiceResult->status,
                ], $stringAnalyzerServiceResult->code, ['Content-Type' => 'application/json']);
            }

            $data = [];
            $stringDatas = $stringAnalyzerServiceResult->data;
            foreach ($stringDatas as $key => $value) {
                //? prepare data
                $data[] = (object) [
                    'id' => $value->hash_value,
                    'value' => $value->input_string,
                    'properties' => [
                        'is_palindrome' => (bool) $value->is_palindrome,
                        'length' => (int) $value->length,
                        'word_count' => (int) $value->word_count,
                        'unique_characters' => (int) $value->unique_character_count,
                        'sha256_hash' => (string) $value->hash_value,
                        'character_frequency_map' => $value->character_frequency_map,
                    ],
                    'created_at' => $value->created_at,
                ];
            }

            //? return response
            return response()->json([
                // "status" => $stringAnalyzerServiceResult->status,
                // "message" => $stringAnalyzerServiceResult->message,
                "data" => $data,
                "count" => $stringAnalyzerServiceResult->counts,
                "filters_applied" => $stringAnalyzerServiceResult->filters_applied,
            ], $stringAnalyzerServiceResult->code, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            Log::error("Error occured: " . $e->getMessage());
            return response()->json([
                'message' => "An error occurred while processing the request.",
                "status" => "error",
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStringAnalyzer $request, StringAnalyzerService $stringAnalyzerService): JsonResponse
    {
        try {
            $data = $request->validated();

            //? process data in the string analyzer service
            $stringAnalyzerServiceResult = $stringAnalyzerService->createNewStringAnalysis($data);

            //? check for error status from service
            if ($stringAnalyzerServiceResult->status === "error") {
                return response()->json([
                    "message" => $stringAnalyzerServiceResult->message,
                    "status" => $stringAnalyzerServiceResult->status,
                ], $stringAnalyzerServiceResult->code);
            }

            // dd($stringAnalyzerServiceResult->data);
            //? prepare data for response
            $data = [
                'id' => $stringAnalyzerServiceResult->data->hash_value,
                'value' => $stringAnalyzerServiceResult->data->input_string,
                "properties" => [
                    'is_palindrome' => (bool) $stringAnalyzerServiceResult->data->is_palindrome,
                    'length' => (int) $stringAnalyzerServiceResult->data->length,
                    'word_count' => (int) $stringAnalyzerServiceResult->data->word_count,
                    'unique_characters' => (int) $stringAnalyzerServiceResult->data->unique_character_count,
                    'sha256_hash' => (string) $stringAnalyzerServiceResult->data->hash_value,
                    'character_frequency_map' => $stringAnalyzerServiceResult->data->character_frequency_map,
                ],
                'created_at' => $stringAnalyzerServiceResult->data->created_at,
            ];

            //? return response
            return response()->json([
                'message' => $stringAnalyzerServiceResult->message,
                "status" => $stringAnalyzerServiceResult->status,
                "data" => $data,
            ], $stringAnalyzerServiceResult->code, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "An error occurred while processing the request.",
                "status" => "error",
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $value, StringAnalyzerService $stringAnalyzerService): JsonResponse
    {
        try {
            //? check if value is provided
            if (empty($value)) {
                return response()->json([
                    'message' => 'The value parameter is required.',
                    'status' => 'error',
                ], 400);
            }

            //? fecth string analyisis from the service
            $stringAnalyzerServiceResult = $stringAnalyzerService->getSpecificString($value);

            // dd($stringAnalyzerServiceResult);

            //? check for error status from service
            if ($stringAnalyzerServiceResult->status === "error") {
                return response()->json([
                    "message" => $stringAnalyzerServiceResult->message,
                    "status" => $stringAnalyzerServiceResult->status,
                ], $stringAnalyzerServiceResult->code);
            }

            //? prepare data to send 
            $data = [
                'id' => $stringAnalyzerServiceResult->data->hash_value,
                'value' => $stringAnalyzerServiceResult->data->input_string,
                "properties" => [
                    'is_palindrome' => $stringAnalyzerServiceResult->data->is_palindrome,
                    'length' => $stringAnalyzerServiceResult->data->length,
                    'word_count' => $stringAnalyzerServiceResult->data->word_count,
                    'unique_characters' => $stringAnalyzerServiceResult->data->unique_character_count,
                    'sha256_hash' => $stringAnalyzerServiceResult->data->hash_value,
                    'character_frequency_map' => $stringAnalyzerServiceResult->data->character_frequency_map,
                ],
                'created_at' => $stringAnalyzerServiceResult->data->created_at,
            ];

            // dd($data);

            //? return response
            return response()->json([
                'status' => 'success',
                'message' => $stringAnalyzerServiceResult->message,
                'data' => $data,
            ], $stringAnalyzerServiceResult->code, ['Content-Type' => 'application/json']);
        } catch (\Exception $e) {
            Log::error("Error occured while processing this request: " . $e->getMessage());

            return response()->json([
                'message' => "An error occurred while processing the request.",
                "status" => "error",
            ], 500);
        }
    }

    public function filterByNaturalLanguage(Request $request, StringAnalyzerService $stringAnalyzerService)
    {
        try {
            $query = $request->query("query");


            //? if query does not exist
            if (!$query) {
                return response()->json([
                    "message" => "Missing natural query"
                ], 400);
            }

            $stringAnalyzerServiceResult = $stringAnalyzerService->filterByNaturalLanguage($query);
            // dd($stringAnalyzerServiceResult);

            //? if string analyzer service is a success
            if ($stringAnalyzerServiceResult->status === 'success') {
                return response()->json([
                    'data' => $stringAnalyzerServiceResult->data,
                    'count' => $stringAnalyzerServiceResult->count,
                    'interpreted_query' => $stringAnalyzerServiceResult->interpreted_query,
                ], $stringAnalyzerServiceResult->code);
            }


            //? return error response
            return response()->json([
                "message" => $stringAnalyzerServiceResult->message
            ], $stringAnalyzerServiceResult->code);
        } catch (\Exception $e) {
            Log::error("Error occured while processing this request: " . $e->getMessage());

            return response()->json([
                'message' => "An error occurred while processing the request.",
                "status" => "error",
            ], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $value, StringAnalyzerService $stringAnalyzerService)
    {
        try {
            $stringAnalyzerServiceResult = $stringAnalyzerService->deleteString($value);

            //? check if error status was returned
            if ($stringAnalyzerServiceResult->status === 'error') {
                return response()->json([
                    "message" => $stringAnalyzerServiceResult->message,
                    "status" => $stringAnalyzerServiceResult->status,
                ], $stringAnalyzerServiceResult->code);
            }

            // dd($stringAnalyzerServiceResult);

            //? return no content response
            return response()->noContent();
        } catch (\Exception $e) {
            Log::error("Error occured while processing this request: " . $e->getMessage());

            return response()->json([
                'message' => "An error occurred while processing the request.",
                "status" => "error",
            ], 500);
        }
    }
}
