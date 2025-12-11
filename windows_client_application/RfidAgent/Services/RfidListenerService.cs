using System;
using System.IO.Ports;
using System.Threading.Tasks;
using System.Collections.Concurrent;
using System.Diagnostics;
using System.Text;

namespace RfidAgent.Services
{
    public class RfidListenerService : IDisposable
    {
        private SerialPort? _serialPort;
        private readonly ConcurrentDictionary<string, DateTime> _debounceCache = new();
        private readonly TimeSpan _debounceDuration = TimeSpan.FromSeconds(3);

        public event EventHandler<string>? OnBadgeScanned;
        public event EventHandler<bool>? OnConnectionStatusChanged;

        public bool IsConnected => _serialPort?.IsOpen ?? false;

        public void Start(string portName, int baudRate, int dataBits, string parityStr, string stopBitsStr)
        {
            Stop();

            try
            {
                Parity parity = Enum.TryParse(parityStr, true, out Parity p) ? p : Parity.None;
                StopBits stopBits = Enum.TryParse(stopBitsStr, true, out StopBits s) ? s : StopBits.One;

                _serialPort = new SerialPort(portName, baudRate, parity, dataBits, stopBits);
                _serialPort.DataReceived += SerialPort_DataReceived;
                _serialPort.Open();
                Debug.WriteLine($"[RfidListener] Listening on {portName} {baudRate} {parity} {dataBits} {stopBits}");
                OnConnectionStatusChanged?.Invoke(this, true);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"[RfidListener] Error opening port: {ex.Message}");
                OnConnectionStatusChanged?.Invoke(this, false);
            }
        }

        public void Stop()
        {
            if (_serialPort != null)
            {
                try
                {
                    if (_serialPort.IsOpen)
                        _serialPort.Close();
                    
                    _serialPort.DataReceived -= SerialPort_DataReceived;
                    _serialPort.Dispose();
                }
                catch (Exception ex) 
                {
                    Debug.WriteLine($"[RfidListener] Error closing port: {ex.Message}");
                }
                finally
                {
                    _serialPort = null;
                }
            }
            OnConnectionStatusChanged?.Invoke(this, false);
        }

        private void SerialPort_DataReceived(object sender, SerialDataReceivedEventArgs e)
        {
            if (_serialPort == null || !_serialPort.IsOpen) return;

            try
            {
                string rawData = _serialPort.ReadExisting();
                // Simple parsing: buffer until newline or treat whole string as UID if it comes in one chunk.
                // For many RFID readers, they send UID + \r\n.
                // We'll clean up the string.
                
                // Note: serial data might come in fragments. For simplicity in this demo,
                // we assume the reader sends fast enough or we check for common terminators.
                // A more robust implementation would buffer characters until a terminator.
                
                ProcessScan(rawData);
            }
            catch (Exception ex)
            {
                Debug.WriteLine($"[RfidListener] Read error: {ex.Message}");
            }
        }

        // Method to call from Keyboard/HID input directly if we hook that up too
        public void ProcessScan(string rawInput)
        {
            string cleanUid = rawInput.Trim().Replace("\r", "").Replace("\n", "");
            
            if (string.IsNullOrWhiteSpace(cleanUid)) return;

            if (ShouldDebounce(cleanUid))
            {
                Debug.WriteLine($"[RfidListener] Debounced {cleanUid}");
                return;
            }

            Debug.WriteLine($"[RfidListener] Scan detected: {cleanUid}");
            OnBadgeScanned?.Invoke(this, cleanUid);
        }

        private bool ShouldDebounce(string uid)
        {
            var now = DateTime.Now;
            if (_debounceCache.TryGetValue(uid, out DateTime lastScanTime))
            {
                if ((now - lastScanTime) < _debounceDuration)
                {
                    return true;
                }
            }

            // Update cache
            _debounceCache[uid] = now;
            
            // Cleanup old entries periodically if needed, but for a kiosk generic dictionary is likely fine for specific day usage
            // or we could purge entries older than 3s here.
            
            return false;
        }

        public void Dispose()
        {
            Stop();
        }
    }
}
