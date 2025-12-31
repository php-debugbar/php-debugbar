# HTTP drivers

Some features of the debug bar requires sending http headers or
using the session. Many frameworks implement their own mechanism
on top of PHP native features.

To make integration with other frameworks as easy as possible,
the `DebugBar` object uses an instance of `DebugBar\HttpDriverInterface`
to access those features.

`DebugBar\PhpHttpDriver`, which uses native PHP mechanisms, is provided
and will be used if no other driver are specified.
