# Module Mapping

Module maps are navigation indexes, not duplicated source code.

`.ai-context/MODULE-MAP.md` should contain one compact section per module: purpose, entry points, module document, and critical dependencies.

Create `.ai-context/modules/<module>.md` only for repeatedly touched or expensive-to-rediscover modules. Include boundaries, entry points, implementation areas, data, interfaces, focused tests, dependencies, and guardrails.

Do not include full source snippets, exhaustive listings, or volatile line numbers. Mark uncertain information as `Needs verification`.
