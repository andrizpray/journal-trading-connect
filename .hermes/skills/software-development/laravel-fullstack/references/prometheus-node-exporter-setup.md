# Prometheus + Node Exporter Setup on Ubuntu

## Node Exporter

```bash
cd /tmp
wget https://github.com/prometheus/node_exporter/releases/download/v1.8.2/node_exporter-1.8.2.linux-amd64.tar.gz
tar xzf node_exporter-1.8.2.linux-amd64.tar.gz
sudo cp node_exporter-1.8.2.linux-amd64/node_exporter /usr/local/bin/
rm -rf node_exporter-1.8.2.linux-amd64*
```

Systemd service (bind to localhost):
```ini
[Unit]
Description=Node Exporter
After=network.target
[Service]
User=nobody
ExecStart=/usr/local/bin/node_exporter --web.listen-address=127.0.0.1:9100
Restart=always
[Install]
WantedBy=multi-user.target
```

## Prometheus

```bash
cd /tmp
wget https://github.com/prometheus/prometheus/releases/download/v2.54.1/prometheus-2.54.1.linux-amd64.tar.gz
tar xzf prometheus-2.54.1.linux-amd64.tar.gz
sudo cp prometheus-2.54.1.linux-amd64/prometheus /usr/local/bin/
sudo cp prometheus-2.54.1.linux-amd64/promtool /usr/local/bin/
sudo mkdir -p /etc/prometheus /var/lib/prometheus
rm -rf prometheus-2.54.1.linux-amd64*
```

Systemd service:
```ini
[Unit]
Description=Prometheus
After=network.target
[Service]
User=prometheus
ExecStart=/usr/local/bin/prometheus \
  --config.file=/etc/prometheus/prometheus.yml \
  --storage.tsdb.path=/var/lib/prometheus \
  --storage.tsdb.retention.time=30d \
  --web.listen-address=127.0.0.1:9090
Restart=always
[Install]
WantedBy=multi-user.target
```

## Config (prometheus.yml)

```yaml
global:
  scrape_interval: 60s
  evaluation_interval: 60s

scrape_configs:
  - job_name: 'node'
    static_configs:
      - targets: ['127.0.0.1:9100']
```

**⚠️ PITFALL: `retention_time` is NOT valid in the YAML config file.** Prometheus v2.54.1 rejects it with `field retention_time not found in type config.plain`. Retention is set ONLY via CLI flag `--storage.tsdb.retention.time`. Do NOT put `retention_time: 30d` under `global:` in the YAML.

## Post-Install

```bash
sudo useradd -r -s /bin/false prometheus 2>/dev/null
sudo chown -R prometheus:prometheus /etc/prometheus /var/lib/prometheus
sudo systemctl daemon-reload
sudo systemctl enable --now node_exporter prometheus
```

**⚠️ PITFALL: `systemctl start` without `enable` means services die on reboot.** Both `node_exporter` and `prometheus` MUST be enabled with `sudo systemctl enable`. Without it, after a VPS reboot all dashboard server metrics show 0 and the monitoring page appears empty. Always verify: `systemctl is-enabled node_exporter prometheus` should both say `enabled`.

**⚠️ PITFALL: `irate()` queries return empty until 5 minutes of data exist.** CPU `irate()` needs 5m of scrape history. Other queries (RAM, disk) work immediately. After starting Node Exporter, wait 60s for Prometheus to scrape, then RAM/disk queries work. CPU may take up to 5 minutes. If `up` metric shows `1` but CPU query returns `[]`, this is normal — just wait.

## Verify

```bash
# Node Exporter
curl -s http://127.0.0.1:9100/metrics | head -3

# Prometheus (wait ~60s for first scrape)
curl -s 'http://127.0.0.1:9090/api/v1/query?query=up'
# Expected: {"status":"success","data":{"result":[{"metric":{"job":"node",...},"value":[...,"1"]}]}}
```

## Common Prometheus Queries (for dashboard)

- CPU: `100 - (avg by(instance) (irate(node_cpu_seconds_total{mode="idle"}[5m])) * 100)`
- RAM: `(1 - node_memory_MemAvailable_bytes / node_memory_MemTotal_bytes) * 100`
- Disk: `(1 - node_filesystem_avail_bytes{fstype!~"tmpfs|overlay"} / node_filesystem_size_bytes{fstype!~"tmpfs|overlay"}) * 100`
- Disk used GB: `(node_filesystem_size_bytes{fstype!~"tmpfs|overlay"} - node_filesystem_avail_bytes{fstype!~"tmpfs|overlay"}) / 1073741824`
- Network in: `irate(node_network_receive_bytes_total{device!="lo"}[5m])`
- Network out: `irate(node_network_transmit_bytes_total{device!="lo"}[5m])`
