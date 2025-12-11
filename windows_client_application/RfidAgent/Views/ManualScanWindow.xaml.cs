using System.Windows;

namespace RfidAgent.Views
{
    public partial class ManualScanWindow : Window
    {
        public string ScannedCode { get; private set; } = string.Empty;

        public ManualScanWindow()
        {
            InitializeComponent();
            InputBox.Focus();
        }

        private void Submit_Click(object sender, RoutedEventArgs e)
        {
            if (!string.IsNullOrWhiteSpace(InputBox.Text))
            {
                ScannedCode = InputBox.Text.Trim();
                DialogResult = true;
                Close();
            }
        }

        private void Cancel_Click(object sender, RoutedEventArgs e)
        {
            DialogResult = false;
            Close();
        }
    }
}
