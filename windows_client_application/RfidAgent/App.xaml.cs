using System.Windows;


namespace RfidAgent
{
    public partial class App : Application
    {
        public static Services.HeartbeatService? HeartbeatService { get; private set; }

        protected override void OnStartup(StartupEventArgs e)
        {
            base.OnStartup(e);

            // Global Exception Handling
            this.DispatcherUnhandledException += (s, args) =>
            {
                MessageBox.Show($"Unhandled Error: {args.Exception.Message}\n{args.Exception.StackTrace}", "Crash", MessageBoxButton.OK, MessageBoxImage.Error);
                args.Handled = true;
            };

            try
            {
                // Init Heartbeat
                var settings = RfidAgent.Models.AppSettings.Load();
                var logger = new RfidAgent.Services.LoggerService(settings);
                var httpClient = new System.Net.Http.HttpClient();
                
                HeartbeatService = new Services.HeartbeatService(httpClient, settings, logger);
                HeartbeatService.Start();

                // Show Main Window manually
                var mainWindow = new MainWindow(HeartbeatService);
                mainWindow.Show();
            }
            catch (System.Exception ex)
            {
                MessageBox.Show($"Startup Error: {ex.Message}", "Startup Failed", MessageBoxButton.OK, MessageBoxImage.Error);
            }
        }
        
        protected override void OnExit(ExitEventArgs e)
        {
            HeartbeatService?.Stop();
            HeartbeatService?.Dispose();
            base.OnExit(e);
        }
    }
}
