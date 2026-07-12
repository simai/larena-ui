# UI package implementation summary

Added a framework-neutral PHP facade that delegates component-key calls to the
application-owned `SmartManager`. The facade fails closed when its resolver is
missing or returns the wrong type; generated backend examples now demonstrate
the same canonical path with `Smart::render('ui.button', ...)`.

The Admin UI Lab asset submits select/boolean changes immediately and debounces
text changes while leaving all rendering on the backend. The reference
normalizer now maps Laravel's `ConvertEmptyStringsToNull` result back to an
explicitly allowed empty select option. This fixes the 422 fallback that made
valid parameter changes appear inert.
