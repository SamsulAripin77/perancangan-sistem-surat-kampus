# Token Reporting

## Precedence

1. Real runtime/API telemetry is authoritative.
2. When real telemetry exists, omit estimated input and output.
3. Otherwise report a planning-only range and its limitations.

## Planning-only format

```text
Token report
- Planning estimate input: ~7k–12k visible tokens
- Planning estimate output: ~800–1.5k visible tokens
- Actual token: unavailable
- Note: not an API billing forecast; internal calls, repeated context, tools, cache, and reasoning may not be represented.
```

## Actual format

```text
Token report
- Actual token: input 700,000 / output 500,000
- Cached input: 620,000
- Uncached input: 80,000
- Reasoning token: 120,000
- Total: 1,200,000
- Source: runtime/API telemetry
```
