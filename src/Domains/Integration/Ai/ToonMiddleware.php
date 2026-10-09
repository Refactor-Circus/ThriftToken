<?php

namespace RefactorCircus\ThriftToken\Domains\Integration\Ai;

use Closure;
use Laravel\Ai\Gateway\StepResult;
use Laravel\Ai\PendingStep;

/**
 * Laravel AI agent middleware that appends TOON format instructions to each
 * generation step's system instructions.
 *
 * Add to your agent's `middleware()` array so the LLM knows how to interpret
 * TOON-encoded tool results. Laravel AI rebuilds every step from the agent's
 * own instructions, so the note is appended exactly once per step.
 */
class ToonMiddleware
{
    private const string INSTRUCTION = 'Tool results may be returned in TOON (Token-Oriented Object Notation) format instead of JSON. '
        .'TOON is a line-oriented, indentation-based format: '
        .'Objects use `key: value` pairs with indentation for nesting. '
        .'Arrays declare length and fields in headers: `key[N]{field1,field2}:` followed by one row per item; '
        .'a field written `customer{name,city}` is a nested object whose values fill that many cells of the row. '
        .'`key[N:]{field1,field2}:` is an object whose entries are rows of the form `entryKey: v1,v2`. '
        .'Inline primitive arrays: `key[N]: v1,v2,v3`. Other arrays list one `- item` per line. '
        .'Dotted keys such as `user.name` are ordinary keys. '
        .'Parse TOON tool results the same way you would parse JSON tool results.';

    /**
     * @param  Closure(PendingStep): StepResult  $next
     */
    public function handle(PendingStep $step, Closure $next): StepResult
    {
        return $next($step->withInstructions(
            trim(($step->instructions ?? '')."\n\n".self::INSTRUCTION),
        ));
    }
}
