import { Component, ElementRef, OnDestroy, OnInit, ViewChild, signal } from '@angular/core';
import Chart from 'chart.js/auto';
import { DashboardDataService } from '../../services/dashboard-data.service';

@Component({
  selector: 'app-satisfaction-chart',
  imports: [],
  templateUrl: './satisfaction-chart.html',
  styleUrl: './satisfaction-chart.css',
})
export class SatisfactionChart implements OnInit, OnDestroy {
  @ViewChild('canvas') canvasRef!: ElementRef<HTMLCanvasElement>;

  media = signal(0);
  totalAvaliacoes = signal(0);
  carregando = signal(true);

  private chart: Chart | null = null;
  private pollTimer: ReturnType<typeof setInterval> | null = null;

  constructor(private dados: DashboardDataService) {}

  ngOnInit(): void {
    this.carregar();
    this.pollTimer = setInterval(() => this.carregar(true), 5000);
  }

  ngOnDestroy(): void {
    this.chart?.destroy();
    if (this.pollTimer) clearInterval(this.pollTimer);
  }

  private carregar(silencioso = false): void {
    if (!silencioso) this.carregando.set(true);

    this.dados.satisfacao().subscribe({
      next: (resposta) => {
        this.media.set(resposta.media);
        this.totalAvaliacoes.set(resposta.totalAvaliacoes);
        this.desenhar(resposta.distribuicao.map((p) => Number(p.valor)));
        this.carregando.set(false);
      },
      error: () => this.carregando.set(false),
    });
  }

  private desenhar(valores: number[]): void {
    if (!this.canvasRef) return;

    const labels = ['1★', '2★', '3★', '4★', '5★'];

    if (this.chart) {
      this.chart.data.datasets[0].data = valores;
      this.chart.update();
      return;
    }

    this.chart = new Chart(this.canvasRef.nativeElement, {
      type: 'bar',
      data: {
        labels,
        datasets: [
          {
            data: valores,
            backgroundColor: ['#cf8b8b', '#cf8b8b', '#e0b84a', '#8fc79a', '#8fc79a'],
            borderRadius: 3,
            maxBarThickness: 46,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        animation: { duration: 400 },
        plugins: {
          legend: { display: false },
          tooltip: {
            backgroundColor: '#141416',
            borderColor: '#3a3a40',
            borderWidth: 1,
            titleColor: '#f2f0ec',
            bodyColor: '#a8a8ae',
            padding: 10,
          },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: '#a8a8ae', font: { size: 13 } },
          },
          y: {
            beginAtZero: true,
            ticks: { stepSize: 1, color: '#6d6d73', font: { size: 10, family: 'Arial' } },
            grid: { color: '#2c2c30' },
          },
        },
      },
    });
  }
}
