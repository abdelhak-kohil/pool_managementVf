using System.Windows;
using System.Security.Cryptography;
using System.Text;
using RfidAgent.Models;

namespace RfidAgent.Views
{
    public partial class LoginWindow : Window
    {
        public bool IsAuthenticated { get; private set; } = false;

        public LoginWindow()
        {
            InitializeComponent();
            PasswordInput.Focus();
        }

        private void Login_Click(object sender, RoutedEventArgs e)
        {
            var settings = AppSettings.Load();
            string inputHash = ComputeSha256Hash(PasswordInput.Password);

            // Default to 'admin' if hash is empty or matches
            // In a real app, 'admin' hash is 8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918
            if (inputHash == settings.AdminPasswordHash)
            {
                IsAuthenticated = true;
                Close();
            }
            else
            {
                MessageBox.Show("Invalid Password", "Security", MessageBoxButton.OK, MessageBoxImage.Warning);
                PasswordInput.Clear();
                PasswordInput.Focus();
            }
        }

        private void Cancel_Click(object sender, RoutedEventArgs e)
        {
            IsAuthenticated = false;
            Close();
        }

        private static string ComputeSha256Hash(string rawData)
        {
            using (SHA256 sha256Hash = SHA256.Create())
            {
                byte[] bytes = sha256Hash.ComputeHash(Encoding.UTF8.GetBytes(rawData));
                StringBuilder builder = new StringBuilder();
                for (int i = 0; i < bytes.Length; i++)
                {
                    builder.Append(bytes[i].ToString("x2"));
                }
                return builder.ToString();
            }
        }
    }
}
