using System.Windows;

namespace RfidAgent
{
    public partial class MainWindow : Window
    {
        public MainWindow(Services.HeartbeatService? heartbeatService)
        {
            InitializeComponent();
            DataContext = new ViewModels.MainViewModel(heartbeatService);
            ApplyWindowSettings();
        }

        private void ApplyWindowSettings()
        {
            var settings = RfidAgent.Models.AppSettings.Load();
            
            // Kiosk Mode Logic
            if (settings.ScreenLayout == RfidAgent.Models.LayoutPreset.MinimalKiosk)
            {
                this.WindowState = WindowState.Maximized;
                this.ResizeMode = ResizeMode.NoResize;
                this.Topmost = true;
            }
            else
            {
                // Custom Chrome defaults
                this.WindowState = WindowState.Normal;
                this.ResizeMode = ResizeMode.CanResize;
                this.Topmost = false;
            }
        }

        private void Header_MouseDown(object sender, System.Windows.Input.MouseButtonEventArgs e)
        {
            if (e.ChangedButton == System.Windows.Input.MouseButton.Left)
                this.DragMove();
        }

        private void Minimize_Click(object sender, RoutedEventArgs e)
        {
            this.WindowState = WindowState.Minimized;
        }

        private void Maximize_Click(object sender, RoutedEventArgs e)
        {
            if (this.WindowState == WindowState.Maximized)
                this.WindowState = WindowState.Normal;
            else
                this.WindowState = WindowState.Maximized;
        }

        private void Close_Click(object sender, RoutedEventArgs e)
        {
            Application.Current.Shutdown();
        }
    }
}