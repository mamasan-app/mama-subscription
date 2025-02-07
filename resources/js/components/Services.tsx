import { CreditCard, Bell, Globe, LayoutDashboard } from "lucide-react";
import { Button } from "@/components/ui/button";

const services = [
  {
    icon: CreditCard,
    title: "Planes de Pago",
    description: "Configura planes mensuales, trimestrales o anuales",
  },
  {
    icon: Bell,
    title: "Domiciliación Bancaria",
    description: "Automatiza los cobros recurrentes de tus clientes",
  },
  {
    icon: Globe,
    title: "Conciliación Multimoneda",
    description: "Maneja bolívares y divisas",
  },
  {
    icon: LayoutDashboard,
    title: "Panel de Control",
    description: "Gestiona todos tus cobros desde un solo lugar",
  },
];

export const Services = () => {
  return (
    <div className="py-20 bg-gray-50">
      <div className="container mx-auto px-6">
        <h2 className="text-3xl font-bold text-center mb-12">
          Nuestros Servicios
        </h2>
        <div className="grid md:grid-cols-2 gap-8">
          {services.map((service) => (
            <div
              key={service.title}
              className="bg-white p-8 rounded-lg shadow-sm hover:shadow-md transition-shadow"
            >
              <div className="flex items-start gap-4">
                <div className="p-3 bg-primary/10 rounded-lg">
                  <service.icon className="w-6 h-6 text-primary" />
                </div>
                <div>
                  <h3 className="text-xl font-semibold mb-2">{service.title}</h3>
                  <p className="text-gray-600 mb-4">{service.description}</p>
                  <Button variant="link" className="text-primary p-0">
                    Solicitar más información →
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};