# Many Notes Project Reference

## Commands
- **Run all tests**: `composer test`
- **Run single test**: `vendor/bin/pest tests/Path/To/TestFile.php::testFunctionName`
- **Test units only**: `composer test:unit`
- **Check code style**: `composer test:lint`
- **Type checking**: `composer test:types`
- **Dev build**: `npm run dev`
- **Production build**: `npm run build`

## Code Style
- **PHP**: PSR-12 with Laravel Pint (preset Laravel)
- **Static Analysis**: PHPStan level 10 (maximum strictness)
- **Classes**: Should be final when possible
- **Methods**: Prefer private over protected
- **Types**: Strict type declarations required
- **Comparisons**: Use === instead of ==
- **Imports**: Global namespace imports for classes, constants, and functions
- **Testing**: Pest PHP framework with Feature/Unit organization

## Architecture
Laravel application for structured note-taking with vaults and hierarchical nodes