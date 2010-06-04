 JSTrim 1.01
-------------

This application compresses JavaScript files by removing/trimming away whitespace or packing
it using a algoritm by Dean Edwards. This application is written i C# .NET so you need the Microsoft .NET 1.1 Runtime
or the Mono Runtime 1.1 if you are using a non Windows platform such as Linux or MacOS X.
The mono version is called jstrim_mono.exe.

This application has to execution modes one is for compressing individual files the other mode is
to use a XML config file with actions to perform the later is more ideal for larger projects
that need batch convertion of numerous JS files.

If a config file is placed in the same directory as the application and if it's called JSTrim.config it will automaticly
be loaded and executed. You may also specify what XML config to use or specify other options.

The console applications has a few options:

Console usage, where src and dest file is optional if you specify a config file:
  jstrim <options> <src file> <dest file>

Options:
  -c, --config <file>
    XML Config file to load instead of the default JSTrim.config

  -f, --force
    Force update of all files. This will skip the last modifcation check of files.
    The JSTrim application will by default only compress files if neeeded.

  -m, --mode <mode>
    Default packing mode. This can be overriden by the XML config file. It can be one of the
    following values low (remove whitespace), docs (Remove JSDoc comments), high (Pack using Dean Edwards algoritm).
    The value of this option is set to low by default, since the high mode is somewhat CPU intensive on the client.

  -q, --quiet
    This will skip any console output. This might come in handy if used in a larger batch script.

Example of usage:
  jstrim test1.js test1_compressed.js
    Compresses test1.js to test1_compressed.js using default low method.

  jstrim --mode high test1.js test1_compressed.js
    Compresses test1.js to test1_compressed.js using the Dan Edvards algoritm.

  jstrim --config ..\conf\simple_example.config
    Performs a batch compression using the specified config file.

  jstrim --force --config ..\conf\simple_example.config
    Performs a batch compression using the specified config file and forces update on all files.
