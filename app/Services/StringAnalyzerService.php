<?php

namespace App\Services;

use App\Models\StringAnalyzer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StringAnalyzerService
{
    public function createNewStringAnalysis(array $data): object
    {
        try {
            $value = $data['value'];

            // dd($this->generateCharacterFrequencyMap($value));

            //? payload to store in db
            $payload = [
                'input_string' => $value,
                'hash_value' => hash('sha256', $value),
                'is_palindrome' => $this->isPalindrome($value),
                'length' => $this->calculateLength($value),
                'word_count' => $this->countWords($value),
                'unique_character_count' => $this->countUniqueCharacters($value),
                'character_frequency_map' => $this->generateCharacterFrequencyMap($value),
            ];

            //? store values in the db
            $stringAnalyer = StringAnalyzer::create($payload);

            return (object) [
                'message' => "String analyzed successfully and strored.",
                'status' => "success",
                'data' => $stringAnalyer,
                "code" => 201,
            ];
        } catch (\Exception $e) {
            Log::error("Error occured in creating string analysis: " . $e->getMessage());

            return (object) [
                'message' => "Failed to analyze string.",
                'status' => "error",
                'data' => null,
                "code" => 500,
            ];
        }
    }

    public function getSpecificString(string $value): object
    {
        try {
            //? fetch string analysis from db based on input value given
            $stringAnalyer = StringAnalyzer::where('input_string', $value)->first();

            if (!$stringAnalyer) {
                return (object) [
                    'status' => 'error',
                    'message' => "String does not exist in the system",
                    'code' => 404,
                ];
            }

            return (object) [
                'status' => 'success',
                'message' => "Data retrived successfully",
                'data' => $stringAnalyer,
                'code' => 200,
            ];
        } catch (\Exception $e) {
            Log::error("Error encountered while fetching data: " . $e->getMessage());

            return (object) [
                "status" => 'error',
                "message" => "Failed to retrieve string",
                'code' => 500,
            ];
        }
    }

    public function getStringsByFitlering(array $queryString)
    {

        try {
            // dd($queryString);
            $query = StringAnalyzer::query();
            $filtersApplied = [];

            //? check if is_palindrome exist in request
            if (array_key_exists('is_palindrome', $queryString)) {
                $raw = $queryString['is_palindrome'];
                $appliedPalindrome = $this->applyPalindromeFilter($query, $raw);
                !empty($appliedPalindrome) ? $filtersApplied['is_palindrome'] = $appliedPalindrome['is_palindrome'] : '';
            }


            //? check for min_length and/or max_length in request and apply correct range filters
            $minLength = array_key_exists('min_length', $queryString) ? (int) $queryString['min_length'] : false;
            $maxLength = array_key_exists('max_length', $queryString) ? (int) $queryString['max_length'] : false;


            $appliedLengthFilter = $this->applyLengthFilter($query, $minLength, $maxLength);
            !empty($appliedLengthFilter) ? $filtersApplied[array_key_first($appliedLengthFilter)] = $appliedLengthFilter[array_key_first($appliedLengthFilter)] : '';

            //? check if word_count exist in request
            if (array_key_exists('word_count', $queryString)) {
                $wordCount = (int) $queryString['word_count'];
                $query->where('word_count', $wordCount);

                //? add value to filters applied
                $filtersApplied['word_count'] = $wordCount;
            }


            //? check if contains_character exist in request
            if (array_key_exists('contains_character', $queryString)) {
                $containsCharacter = $queryString['contains_character'];

                //? build a JSON path for the character key and use a parameter binding to avoid injection issues
                $escapedChar = str_replace('"', '\\"', $containsCharacter);
                $jsonPath = '$."' . $escapedChar . '"';

                // dd($jsonPath);

                //? ensure the character exists in the JSON frequency map and has a count > 0
                $query->whereRaw('JSON_EXTRACT(character_frequency_map, ?) > 0', [$jsonPath]);

                //? add value to filters applied
                $filtersApplied['contains_character'] = $containsCharacter;
            }

            //? execute query and return results
            $results = $query->get();

            // dd($results->toArray());

            return (object) [
                'status' => 'success',
                'message' => "Data retrieved successfully",
                'filters_applied' => $filtersApplied,
                'data' => $results,
                'counts' => count($results),
                'code' => 200,
            ];
        } catch (\Exception $e) {
            Log::error('An error occured while fetching filtered data: ' . $e->getMessage());

            return (object) [
                'status' => 'error',
                "message" => "Failed to retrieve filters",
                "code" => 500,
            ];
        }
    }


    public function filterByNaturalLanguage(string $query)
    {
        try {
            // 1) Parse the natural language into filters
            $parsed = $this->parseNaturalLanguageQuery($query);
            //dd($parsed);

            // 2) Handle parsing errors (400 or 422 returned by parser)
            if (!isset($parsed->status) || $parsed->status !== 'success') {
                // parser returns error object with 'code' and 'message'
                $code = $parsed->code ?? 400;
                return (object)[
                    'status' => 'error',
                    'message' => $parsed->message ?? 'Unable to parse natural language query.',
                    'data' => null,
                    'count' => 0,
                    'interpreted_query' => null,
                    'code' => $code,
                ];
            }

            // 3) Ensure parser provided filters
            $filters = $parsed->filters ?? [];
            if (empty($filters)) {
                return (object)[
                    'status' => 'error',
                    'message' => 'Unable to parse natural language query.',
                    'data' => null,
                    'count' => 0,
                    'interpreted_query' => null,
                    'code' => 400,
                ];
            }

            // dd($filters);

            // 4) Use existing filtering method to get results
            $resultObj = $this->getStringsByFitlering($filters); // note: your existing method name
            // dd($resultObj);

            // 5) If that method returned an error, propagate it
            if (isset($resultObj->status) && $resultObj->status === 'error') {
                return (object)[
                    'status' => 'error',
                    'message' => $resultObj->message ?? 'Failed to retrieve data.',
                    'data' => null,
                    'count' => 0,
                    'interpreted_query' => [
                        'original' => $query,
                        'parsed_filters' => $filters,
                    ],
                    'code' => $resultObj->code ?? 500,
                ];
            }

            // 6) Format the data the same way your controller expects:
            $models = $resultObj->data ?? collect();
            // dd($models->toArray());
            $data = [];
            foreach ($models as $value) {
                $data[] = (object)[
                    'id' => $value->hash_value,
                    'value' => $value->input_string,
                    'properties' => [
                        'is_palindrome' => (bool) $value->is_palindrome,
                        'length' => (int) $value->length,
                        'word_count' => (int) $value->word_count,
                        'unique_character_count' => (int) $value->unique_character_count,
                        'character_frequency_map' => $value->character_frequency_map,
                    ],
                    'created_at' => $value->created_at,
                ];
            }

            // 7) Return success with data + count + interpreted query
            return (object) [
                'status' => 'success',
                'message' => 'Natural language filtered data retrieved successfully.',
                'data' => $data,
                'count' => is_int($resultObj->counts) ? $resultObj->counts : count($data),
                'interpreted_query' => [
                    'original' => $query,
                    'parsed_filters' => $filters,
                ],
                'code' => 200,
            ];
        } catch (\Throwable $e) {
            Log::error("filterByNaturalLanguage error: " . $e->getMessage());

            return (object)[
                'status' => 'error',
                'message' => 'An error occurred while processing the natural language query.',
                'data' => null,
                'count' => 0,
                'interpreted_query' => null,
                'code' => 500,
            ];
        }
    }

    public function parseNaturalLanguageQuery(string $query): object
    {
        try {
            if (empty(trim($query))) {
                throw new \InvalidArgumentException('Empty query string.');
            }

            $query = strtolower(trim($query));

            // dd($query);

            $filters = [
                'is_palindrome' => null,
                'min_length' => null,
                'max_length' => null,
                'contains_character' => null,
                'word_count' => null,
            ];

            //? Detect "palindrome" or "not palindrome"
            if (preg_match('/\bpalindrome|palindromic\b/', $query)) {
                $filters['is_palindrome'] = true;
            }

            if (preg_match('/\bnot\s+palindrome|non-palindrome\b/', $query)) {
                if ($filters['is_palindrome'] === true) {
                    //? Conflict: both palindrome and not palindrome
                    return (object)[
                        'status' => 'error',
                        'message' => 'Query parsed but resulted in conflicting filters.',
                        'code' => 422
                    ];
                }
                $filters['is_palindrome'] = false;
            }

            //? Detect "longer than X"
            if (preg_match('/(longer|greater)\s+than\s+(\d+)/', $query, $matches)) {
                $filters['min_length'] = (int)$matches[2] + 1;
            }

            //? Detect "shorter than X"
            if (preg_match('/(shorter|less)\s+than\s+(\d+)/', $query, $matches)) {
                $filters['max_length'] = (int)$matches[2] - 1;
            }

            //? Detect "exactly X characters"
            if (preg_match('/exactly\s+(\d+)\s+(characters|letters|chars)/', $query, $matches)) {
                $filters['min_length'] = $filters['max_length'] = (int)$matches[1];
            }

            //? Detect "contains" or "containing letter/character/char"
            if (preg_match('/\b(?:contains|containing)\b(?:\s+the)?(?:\s+(?:letter|character|char))?\s+([a-zA-Z])/i', $query, $matches)) {
                $filters['contains_character'] = strtolower($matches[1]);
            }

            //? Detect word count
            if (preg_match('/single\s+word/', $query)) {
                $filters['word_count'] = 1;
            } elseif (preg_match('/two\s+words/', $query)) {
                $filters['word_count'] = 2;
            } elseif (preg_match('/three\s+words/', $query)) {
                $filters['word_count'] = 3;
            } elseif (preg_match('/multiple\s+words|more\s+than\s+one\s+word/', $query)) {
                $filters['word_count'] = '>1';
            }

            //? Filter out nulls
            $appliedFilters = array_filter($filters, fn($v) => !is_null($v));

            if (empty($appliedFilters)) {
                //? Could not interpret anything meaningful
                return (object)[
                    'status' => 'error',
                    'message' => 'Unable to parse natural language query.',
                    'code' => 400
                ];
            }

            return (object)[
                'status' => 'success',
                'message' => 'Natural language query parsed successfully.',
                'filters' => $appliedFilters,
                'code' => 200
            ];
        } catch (\Throwable $e) {
            Log::error('Natural language parsing failed: ' . $e->getMessage());

            return (object)[
                'status' => 'error',
                'message' => 'Unable to parse natural language query.',
                'code' => 400
            ];
        }
    }



    private function applyPalindromeFilter(object $query, string|bool $value): array
    {
        //? normalize common boolean representations to 1 or 0, otherwise skip filter
        $filtersApplied = [];
        $isPalindrome = null;
        if (is_bool($value)) {
            $isPalindrome = $value ? 1 : 0;
        } elseif (is_int($value)) {
            $isPalindrome = $value === 1 ? 1 : 0;
        } elseif (is_string($value)) {
            $normalized = strtolower(trim($value));
            if (in_array($normalized, ['1', 'true', 'yes', 'y'], true)) {
                $isPalindrome = 1;
            } elseif (in_array($normalized, ['0', 'false', 'no', 'n'], true)) {
                $isPalindrome = 0;
            }
        }

        //? apply filter only when we have a valid boolean-like value
        if ($isPalindrome !== null) {
            $query->where('is_palindrome', $isPalindrome);

            //? add value to filters applied
            $filtersApplied['is_palindrome'] = (bool) $isPalindrome;
        }

        return $filtersApplied;
    }

    private function applyLengthFilter(object $query, ?int $min = null, ?int $max = null): array
    {
        $filtersApplied = [];
        if ($min && $max) {

            //? ensure proper ordering: swap if min is greater than max
            if ($min > $max) {
                [$min, $max] = [$max, $min];
            }

            $query->whereBetween('length', [$min, $max]);

            //? add values to filters applied
            $filtersApplied['min_length'] = $min;
            $filtersApplied['max_length'] = $max;
        } elseif ($min) {

            //? length must be greater than or equal to minLength
            $query->where('length', '>=', $min);
            $filtersApplied['min_length'] = $min;
        } elseif ($max) {

            //? length must be less than or equal to maxLength
            $query->where('length', '<=', $max);
            $filtersApplied['max_length'] = $max;
        }

        return $filtersApplied;
    }

    public function deleteString(string $value)
    {
        try {
            $stringAnalyer = StringAnalyzer::where('input_string', $value)->firstOrFail();
            $stringAnalyer->delete();

            return (object) [
                'status' => 'success',
                'message' => "Record with input_string '{$value}' deleted successfully",
                'code' => 204,
            ];
        } catch (ModelNotFoundException $e) {
            return (object) [
                'status' => 'error',
                'message' => 'String does not exist in the system',
                'code' => 404,
            ];
        } catch (\Exception $e) {
            Log::error("An error occurred while deleting string: " . $e->getMessage());

            return (object) [
                'status' => 'error',
                'message' => "An unexpected "
            ];
        }
    }

    private function isPalindrome(string $value): bool
    {
        try {
            //? normalize: remove non-letters/numbers and lowercase (multibyte safe)
            $normalized = preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower($value, 'UTF-8'));

            if ($normalized === null) {
                throw new \RuntimeException("Failed to normalize string for palindrome check.");
            }
            $reversed = Str::reverse($normalized);
            return $normalized === $reversed;
        } catch (\Throwable $e) {
            //? handle or log the exception as needed
            Log::error("Error checking palindrome: " . $e->getMessage());
            throw $e;
        }
    }

    private function calculateLength(string $value): int|null
    {
        try {
            return Str::length($value);
        } catch (\Throwable $e) {
            Log::error("Error calculating string length: " . $e->getMessage());
            throw $e;
        }
    }

    private function countWords(string $value): int|null
    {
        try {
            $words = preg_split('/\s+/u', trim($value));
            if ($words === false) {
                throw new \RuntimeException("Failed to split string into words.");
            }

            return count($words);
        } catch (\Throwable $e) {
            Log::error("Error counting words: " . $e->getMessage());
            throw $e;
        }
    }

    private function countUniqueCharacters(string $value): int|null
    {
        try {
            $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
            if ($characters === false) {
                throw new \RuntimeException("Failed to split string into characters.");
            }

            // dd($characters);
            $uniqueCharacters = array_unique($characters);
            return count($uniqueCharacters);
        } catch (\Throwable $e) {
            Log::error("Error counting unique characters: " . $e->getMessage());
            throw $e;
        }
    }

    private function generateCharacterFrequencyMap(string $value)
    {
        try {
            $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
            if ($characters === false) {
                throw new \RuntimeException("Failed to split string into characters.");
            }

            // dd($characters);
            $frequencyMap = [];
            foreach ($characters as $char) {
                if (array_key_exists($char, $frequencyMap)) {
                    $frequencyMap[$char]++;
                } else {
                    $frequencyMap[$char] = 1;
                }
            }

            return $frequencyMap;
        } catch (\Throwable $e) {
            Log::error("Error generating character frequency map: " . $e->getMessage());
            throw $e;
        }
    }
}
