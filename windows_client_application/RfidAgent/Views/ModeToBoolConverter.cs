using System;
using System.Globalization;
using System.Windows.Data;
using RfidAgent.Models;

namespace RfidAgent.Views
{
    public class ModeToBoolConverter : IValueConverter
    {
        public object Convert(object value, Type targetType, object parameter, CultureInfo culture)
        {
            if (value is RfidMode mode && parameter is string target)
            {
                if (target == "COM") return mode == RfidMode.COM;
                if (target == "HID") return mode == RfidMode.HID;
            }
            return false;
        }

        public object ConvertBack(object value, Type targetType, object parameter, CultureInfo culture)
        {
            throw new NotImplementedException();
        }
    }
}
