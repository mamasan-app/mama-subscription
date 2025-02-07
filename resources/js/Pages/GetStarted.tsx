import { Button } from "@/components/ui/button";
import { Input } from "@/components/ui/input";
import { useNavigate } from "react-router-dom";
import { useState } from "react";
import { useToast } from "@/components/ui/use-toast";

const GetStarted = () => {
  const navigate = useNavigate();
  const { toast } = useToast();
  const [email, setEmail] = useState("");
  const [phone, setPhone] = useState("");

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!email || !phone) {
      toast({
        title: "Error",
        description: "Por favor completa todos los campos",
        variant: "destructive",
      });
      return;
    }
    // Here you would typically send this data to your backend
    toast({
      title: "¡Gracias por tu interés!",
      description: "Nos pondremos en contacto contigo pronto.",
    });
    navigate("/");
  };

  return (
    <div className="min-h-screen bg-white py-20">
      <div className="container mx-auto px-6">
        <div className="max-w-4xl mx-auto">
          <h1 className="text-4xl font-bold mb-8 text-center">
            ¿Cómo funciona CobraFácil?
          </h1>

          <div className="mb-16 space-y-8">
            <section className="bg-gray-50 p-8 rounded-lg">
              <h2 className="text-2xl font-semibold mb-4">Proceso Simple</h2>
              <div className="grid md:grid-cols-3 gap-6">
                <div className="text-center">
                  <div className="bg-primary/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span className="text-2xl font-bold text-primary">1</span>
                  </div>
                  <h3 className="font-semibold mb-2">Regístrate</h3>
                  <p className="text-gray-600">
                    Completa el formulario con tus datos básicos
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-primary/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span className="text-2xl font-bold text-primary">2</span>
                  </div>
                  <h3 className="font-semibold mb-2">Configura</h3>
                  <p className="text-gray-600">
                    Personaliza tus opciones de cobro según tus necesidades
                  </p>
                </div>
                <div className="text-center">
                  <div className="bg-primary/10 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                    <span className="text-2xl font-bold text-primary">3</span>
                  </div>
                  <h3 className="font-semibold mb-2">¡Comienza a cobrar!</h3>
                  <p className="text-gray-600">
                    Recibe pagos automáticamente en tu cuenta
                  </p>
                </div>
              </div>
            </section>

            <section className="bg-gray-50 p-8 rounded-lg">
              <h2 className="text-2xl font-semibold mb-4">Nuestras Tarifas</h2>
              <div className="space-y-4">
                <div className="flex justify-between items-center p-4 bg-white rounded-lg shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Básico</h3>
                    <p className="text-gray-600">Ideal para pequeños negocios</p>
                  </div>
                  <p className="text-xl font-bold text-primary">$29/mes</p>
                </div>
                <div className="flex justify-between items-center p-4 bg-white rounded-lg shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Pro</h3>
                    <p className="text-gray-600">Para negocios en crecimiento</p>
                  </div>
                  <p className="text-xl font-bold text-primary">$49/mes</p>
                </div>
                <div className="flex justify-between items-center p-4 bg-white rounded-lg shadow-sm">
                  <div>
                    <h3 className="font-semibold">Plan Enterprise</h3>
                    <p className="text-gray-600">Soluciones personalizadas</p>
                  </div>
                  <p className="text-xl font-bold text-primary">Contactar</p>
                </div>
              </div>
            </section>

            <section className="bg-gray-50 p-8 rounded-lg">
              <h2 className="text-2xl font-semibold mb-6">
                ¿Listo para comenzar?
              </h2>
              <form onSubmit={handleSubmit} className="space-y-4 max-w-md mx-auto">
                <div>
                  <label
                    htmlFor="email"
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    Correo Electrónico
                  </label>
                  <Input
                    id="email"
                    type="email"
                    value={email}
                    onChange={(e) => setEmail(e.target.value)}
                    placeholder="tu@email.com"
                    className="w-full"
                  />
                </div>
                <div>
                  <label
                    htmlFor="phone"
                    className="block text-sm font-medium text-gray-700 mb-1"
                  >
                    Teléfono
                  </label>
                  <Input
                    id="phone"
                    type="tel"
                    value={phone}
                    onChange={(e) => setPhone(e.target.value)}
                    placeholder="+58 (424) 123-4567"
                    className="w-full"
                  />
                </div>
                <Button
                  type="submit"
                  className="w-full bg-primary hover:bg-primary-dark text-white"
                >
                  Comenzar Ahora
                </Button>
              </form>
            </section>
          </div>
        </div>
      </div>
    </div>
  );
};

export default GetStarted;