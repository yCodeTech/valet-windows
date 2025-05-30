# Upgrading

-   If you have any issues with your drivers, all custom drivers (including the `SampleValetDriver` published by previous versions of Valet) must have the following:

    -   Have the namespace of `Valet\Drivers\Custom`.

    -   Extend the new namespaced drivers instead of the old non-namespaced drivers by specifying a `use` keyword like `use Valet\Drivers\ValetDriver;`

    See the [new SampleValetDriver](https://github.com/yCodeTech/valet-windows/blob/master/cli/stubs/SampleValetDriver.php) for an example.
